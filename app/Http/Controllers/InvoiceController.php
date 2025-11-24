<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\SimplePdf;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    // ===== List + Search =====
    public function index()
    {
        $q = request('q');

        $invoices = Invoice::query()
            ->onlyInvoices()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('number','like',"%{$q}%")
                      ->orWhere('customer_name','like',"%{$q}%");
                });
            })
            ->select('id','number','customer_name','issue_date','status','total','created_at')
            ->orderByDesc('issue_date')
            ->paginate(15);

        return view('invoices.index', [
            'invoices' => $invoices,
            'q' => $q,
        ]);
    }

    // ===== Helpers =====
    private function findByKey(string $key): Invoice
    {
        $invoiceQuery = Invoice::query()->onlyInvoices();

        $invoice = ctype_digit($key)
            ? $invoiceQuery->whereKey($key)->first()
            : $invoiceQuery->where('number',$key)->first();

        abort_unless($invoice, 404, 'Invoice not found');
        return $invoice;
    }

    // ===== Create form (HTML view) =====
    public function create()
    {
        return view('invoices.create', [
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    // ===== Store action =====
    public function store(Request $request)
    {
        $data = $request->validate([
            'number'               => ['nullable','string','max:255', Rule::unique('invoices','number')],
            'quotation_number'     => 'nullable|string|max:255',
            'customer_id'          => 'nullable|integer|exists:customers,id',
            'customer_name'        => 'required|string|max:255',
            'customer_address'     => 'nullable|string',
            'customer_tax_id'      => 'nullable|string|max:50',
            'customer_branch_type' => 'nullable|string|max:20',
            'customer_branch_code' => 'nullable|string|max:20',
            'issue_date'           => 'nullable|date',
            'due_date'             => 'nullable|date',
            'discount_percent'     => 'nullable|numeric',
            'vat_enabled'          => 'sometimes|boolean',
            'tax_rate'             => 'nullable|numeric',
            'subtotal'             => 'nullable|numeric',
            'tax'                  => 'nullable|numeric',
            'total'                => 'nullable|numeric',
            'status'               => ['nullable','string','max:50', Rule::in($this->allowedStatusValues())],
            'currency'             => 'nullable|string|max:10',
        ]);

        $data['vat_enabled'] = $request->boolean('vat_enabled');
        $data['status'] = $this->normalizeStatus($data['status'] ?? '');

        $invoice = Invoice::create($data);

        $slug = $invoice->number ?: $invoice->id;

        return redirect()
            ->route('invoices.show', $slug)
            ->with('ok', 'Created');
    }

    // ===== Show (HTML view) =====
    public function show(string $key)
    {
        $invoice = $this->findByKey($key);
        $receiptsAvailable = Schema::hasTable('receipts');

        $relations = ['items' => fn ($q) => $q->orderBy('id'), 'quotation'];
        if ($receiptsAvailable) {
            $relations[] = 'receipt';
        }

        $invoice->loadMissing($relations);
        if (!$receiptsAvailable) {
            $invoice->setRelation('receipt', null);
        }

        return view('invoices.show', compact('invoice', 'receiptsAvailable'));
    }

    public function pdf(string $key)
    {
        $invoice = $this->findByKey($key);
        $invoice->loadMissing([
            'items' => fn ($q) => $q->orderBy('id'),
            'quotation',
        ]);

        if (class_exists(Dompdf::class)) {
            $html = view('invoices.pdf', ['invoice' => $invoice])->render();

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
            $payload = SimplePdf::invoice($invoice);
        }

        $filename = ($invoice->number ?? 'invoice').'.pdf';
        $dir = storage_path('app/invoices');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = $dir.'/'.$filename;
        file_put_contents($path, $payload);

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    // ===== Edit form (HTML view) =====
    public function edit(string $key)
    {
        $invoice = $this->findByKey($key);
        return view('invoices.edit', [
            'invoice'        => $invoice,
            'statusOptions'  => $this->statusOptions(),
        ]);
    }

    // ===== Update action =====
    public function update(Request $request, string $key)
    {
        $invoice = $this->findByKey($key);

        $data = $request->validate([
            'number'        => ['nullable','string','max:255', Rule::unique('invoices','number')->ignore($invoice->id)],
            'quotation_number' => 'nullable|string|max:255',
            'customer_name' => 'required|string|max:255',
            'issue_date'    => 'nullable|date',
            'status'        => ['required','string','max:50', Rule::in($this->allowedStatusValues())],
            'total'         => 'required|numeric',
        ]);

        $data['status'] = $this->normalizeStatus($data['status']);

        $invoice->update($data);

        // ให้ redirect กลับไปหน้า show โดยใช้เลขเอกสารถ้ามี
        $slug = $invoice->number ?: $invoice->id;
        return redirect()->route('invoices.show', $slug)->with('ok','Updated');
    }

    // ===== Delete action =====
    public function destroy(string $key)
    {
        $invoice = $this->findByKey($key);
        $invoice->delete();

        return redirect()->route('invoices.index')->with('ok', 'Deleted');
    }

    private function statusOptions(): array
    {
        return [
            'pending'   => 'Pending / Waiting for Approval',
            'approved'  => 'Approved',
            'paid'      => 'Paid',
            'cancelled' => 'Cancelled / Void',
        ];
    }

    private function allowedStatusValues(): array
    {
        return array_merge(array_keys($this->statusOptions()), [
            'draft', 'sent', 'unpaid', 'void', 'cancel', 'waiting', 'waiting for approval', 'waiting_for_approval',
        ]);
    }

    private function normalizeStatus(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));
        $normalized = match ($normalized) {
            'draft', 'sent', 'unpaid', 'waiting', 'waiting for approval', 'waiting_for_approval' => 'pending',
            'void', 'cancel' => 'cancelled',
            default => $normalized,
        };

        return in_array($normalized, array_keys($this->statusOptions()), true)
            ? $normalized
            : 'pending';
    }
}
