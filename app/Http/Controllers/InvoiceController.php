<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        $q = request('q');

        $invoices = Invoice::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('number', 'like', "%$q%")
                      ->orWhere('customer_name', 'like', "%$q%");
                });
            })
            ->select('id', 'number', 'customer_name', 'issue_date', 'status', 'total', 'created_at')
            ->orderByDesc('issue_date')
            ->paginate(15);

        return view('invoices.index', [
            'invoices' => $invoices,
            'q' => $q,
        ]);
    }

    public function create()
    {
        return view('invoices.create');
    }

    public function show(Invoice $invoice)
    {
        return redirect()->route('invoices.edit', $invoice);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name'          => ['required','string','max:255'],
            'issue_date'             => ['required','date'],
            'due_date'               => ['nullable','date','after_or_equal:issue_date'],
            'tax_rate'               => ['required','numeric','min:0'],
            'items'                  => ['required','array','min:1'],
            'items.*.description'    => ['required','string','max:255'],
            'items.*.qty'            => ['required','integer','min:1'],
            'items.*.price'          => ['required','numeric','min:0'],
            'items.*.unit'           => ['nullable','string','max:50'],
        ]);

        // คำนวณยอดรวม
        $subtotal = collect($data['items'])->sum(fn($i) => $i['qty'] * $i['price']);
        $tax = $subtotal * ($data['tax_rate'] / 100);
        $total = $subtotal + $tax;

        $invoice = Invoice::create([
            'customer_name' => $data['customer_name'],
            'issue_date'    => $data['issue_date'],
            'due_date'      => $data['due_date'] ?? null,
            'tax_rate'      => $data['tax_rate'],
            'subtotal'      => $subtotal,
            'tax'           => $tax,
            'total'         => $total,
            'status'        => 'draft',
        ]);

        foreach ($data['items'] as $item) {
            $invoice->items()->create($item);
        }

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully.');
    }

    public function edit(Invoice $invoice)
    {
        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'customer_name' => ['required','string','max:255'],
            'issue_date'    => ['required','date'],
            'due_date'      => ['nullable','date','after_or_equal:issue_date'],
            'tax_rate'      => ['required','numeric','min:0'],
            'status'        => ['nullable','string','max:50'],
        ]);

        $invoice->update($data);

        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }
}
