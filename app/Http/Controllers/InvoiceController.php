<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    // ===== List + Search =====
    public function index()
    {
        $q = request('q');

        $invoices = Invoice::query()
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
        $invoice = ctype_digit($key)
            ? Invoice::find($key)
            : Invoice::where('number',$key)->first();

        abort_unless($invoice, 404, 'Invoice not found');
        return $invoice;
    }

    // ===== Create form (HTML view) =====
    public function create()
    {
        return view('invoices.create');
    }

    // ===== Store action =====
    public function store(Request $request)
    {
        $data = $request->validate([
            'number'               => ['nullable','string','max:255', Rule::unique('invoices','number')],
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
            'status'               => 'nullable|string|max:50',
            'currency'             => 'nullable|string|max:10',
        ]);

        $data['vat_enabled'] = $request->boolean('vat_enabled');

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
        $invoice->loadMissing('quotation');
        return view('invoices.show', compact('invoice'));
    }

    // ===== Edit form (HTML view) =====
    public function edit(string $key)
    {
        $invoice = $this->findByKey($key);
        return view('invoices.edit', compact('invoice'));
    }

    // ===== Update action =====
    public function update(Request $request, string $key)
    {
        $invoice = $this->findByKey($key);

        $data = $request->validate([
            'number'        => ['nullable','string','max:255', Rule::unique('invoices','number')->ignore($invoice->id)],
            'customer_name' => 'required|string|max:255',
            'issue_date'    => 'nullable|date',
            'status'        => 'required|string|max:50',
            'total'         => 'required|numeric',
        ]);

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
}
