<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Receipt;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ReceiptController extends Controller
{
    private function findByKey(string $key): Receipt
    {
        $this->ensureReceiptsTable();

        $receipt = ctype_digit($key)
            ? Receipt::find($key)
            : Receipt::where('number', $key)->first();

        abort_unless($receipt, 404, 'Receipt not found');
        return $receipt;
    }

    public function index()
    {
        $this->ensureReceiptsTable();

        $q = request('q');
        $receipts = Receipt::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('number', 'like', "%{$q}%")
                      ->orWhere('customer_name', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('receipts.index', compact('receipts', 'q'));
    }

    public function create(Request $request)
    {
        $this->ensureReceiptsTable();

        $invoice = null;
        if ($request->filled('invoice_id')) {
            $invoice = Invoice::with('items')->find($request->integer('invoice_id'));
        }

        return view('receipts.create', compact('invoice'));
    }

    public function store(Request $request)
    {
        $this->ensureReceiptsTable();

        $data = $request->validate([
            'number' => ['nullable', 'string', 'max:255', Rule::unique('receipts', 'number')],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'integer'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_address' => ['nullable', 'string'],
            'customer_tax_id' => ['nullable', 'string', 'max:50'],
            'customer_branch_type' => ['nullable', 'string', 'max:20'],
            'customer_branch_code' => ['nullable', 'string', 'max:20'],
            'issue_date' => ['nullable', 'date'],
            'total' => ['required', 'numeric'],
            'status' => ['nullable', Rule::in(['draft', 'issued', 'cancelled', 'void'])],
            'currency' => ['nullable', 'string', 'max:10'],
        ]);

        $receipt = Receipt::create($data);
        $slug = $receipt->number ?: $receipt->id;

        return redirect()->route('receipts.show', $slug)->with('ok', 'Receipt created');
    }

    public function show(string $key)
    {
        $this->ensureReceiptsTable();

        $receipt = $this->findByKey($key);
        $receipt->loadMissing('invoice.items');
        return view('receipts.show', compact('receipt'));
    }

    public function edit(string $key)
    {
        $this->ensureReceiptsTable();

        $receipt = $this->findByKey($key);
        $receipt->loadMissing(['invoice.items']);
        $invoice = $receipt->invoice;

        return view('receipts.edit', compact('receipt', 'invoice'));
    }

    public function pdf(string $key)
    {
        $this->ensureReceiptsTable();

        $receipt = $this->findByKey($key);
        $receipt->loadMissing(['invoice.items']);

        if (class_exists(Dompdf::class)) {
            $html = view('receipts.pdf', ['receipt' => $receipt])->render();

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4');
            $dompdf->render();

            $payload = $dompdf->output();
        } else {
            $payload = view('receipts.pdf', ['receipt' => $receipt])->render();
        }

        $filename = ($receipt->number ?? 'receipt').'.pdf';
        $dir = storage_path('app/receipts');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = $dir.'/'.$filename;
        file_put_contents($path, $payload);

        return response()->file($path, [
            'Content-Type'        => class_exists(Dompdf::class) ? 'application/pdf' : 'text/html',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function update(Request $request, string $key)
    {
        $this->ensureReceiptsTable();

        $receipt = $this->findByKey($key);

        $data = $request->validate([
            'number' => ['nullable', 'string', 'max:255', Rule::unique('receipts', 'number')->ignore($receipt->id)],
            'invoice_id' => ['nullable', 'integer', 'exists:invoices,id'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'integer'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_address' => ['nullable', 'string'],
            'customer_tax_id' => ['nullable', 'string', 'max:50'],
            'customer_branch_type' => ['nullable', 'string', 'max:20'],
            'customer_branch_code' => ['nullable', 'string', 'max:20'],
            'issue_date' => ['nullable', 'date'],
            'total' => ['required', 'numeric'],
            'status' => ['nullable', Rule::in(['draft', 'issued', 'cancelled', 'void'])],
            'currency' => ['nullable', 'string', 'max:10'],
        ]);

        $receipt->update($data);
        $slug = $receipt->number ?: $receipt->id;

        return redirect()->route('receipts.show', $slug)->with('ok', 'Receipt updated');
    }

    public function destroy(string $key)
    {
        $this->ensureReceiptsTable();

        $receipt = $this->findByKey($key);
        $receipt->delete();

        return redirect()->route('receipts.index')->with('ok', 'Receipt deleted');
    }

    public function fromInvoice(string $invoiceKey)
    {
        $this->ensureReceiptsTable();

        $invoice = ctype_digit($invoiceKey)
            ? Invoice::find($invoiceKey)
            : Invoice::where('number', $invoiceKey)->first();

        abort_unless($invoice, 404, 'Invoice not found');

        $status = strtolower($invoice->status ?? '');
        abort_if(!in_array($status, ['approved', 'paid'], true), 400, 'Invoice must be approved or paid before issuing a receipt');

        if (method_exists($invoice, 'receipt')) {
            $existing = $invoice->receipt()->first();
            if ($existing) {
                return redirect()->route('receipts.edit', $existing->number ?: $existing->id)
                    ->with('ok', 'Receipt already exists for this invoice');
            }
        }

        $payload = [
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->number,
            'customer_id' => $invoice->customer_id,
            'customer_name' => $invoice->customer_name,
            'customer_address' => $invoice->customer_address,
            'customer_tax_id' => $invoice->customer_tax_id,
            'customer_branch_type' => $invoice->customer_branch_type,
            'customer_branch_code' => $invoice->customer_branch_code,
            'issue_date' => now()->toDateString(),
            'total' => $invoice->total ?? 0,
            'status' => 'draft',
            'currency' => $invoice->currency,
        ];

        $receipt = Receipt::create($payload);
        $slug = $receipt->number ?: $receipt->id;

        return redirect()->route('receipts.edit', $slug)->with('ok', 'Receipt drafted from invoice');
    }

    private function ensureReceiptsTable(): bool
    {
        if (Schema::hasTable('receipts')) {
            return true;
        }

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name');
            $table->text('customer_address')->nullable();
            $table->string('customer_tax_id')->nullable();
            $table->string('customer_branch_type')->nullable();
            $table->string('customer_branch_code')->nullable();
            $table->date('issue_date')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status')->default('draft');
            $table->string('currency')->nullable();
            $table->timestamps();
        });

        return true;
    }
}
