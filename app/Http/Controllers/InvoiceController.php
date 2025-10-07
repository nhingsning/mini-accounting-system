<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::latest()->paginate(20);
        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        return view('invoices.create');
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
        ]);

        // คำนวณยอด
        $subtotal = 0;
        foreach ($data['items'] as &$it) {
            $it['line_total'] = (int)$it['qty'] * (float)$it['price'];
            $subtotal += $it['line_total'];
        }
        $tax   = round($subtotal * ((float)$data['tax_rate'] / 100), 2);
        $total = round($subtotal + $tax, 2);

        // บันทึก
        DB::transaction(function () use ($data, $subtotal, $tax, $total) {
            $invoice = \App\Models\Invoice::create([
                'number'        => now()->format('Ymd') . '-' . str_pad((string)random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                'customer_name' => $data['customer_name'],
                'issue_date'    => $data['issue_date'],
                'due_date'      => $data['due_date'] ?? null,
                'tax_rate'      => $data['tax_rate'],
                'subtotal'      => $subtotal,
                'tax'           => $tax,
                'total'         => $total,
                'status'        => 'unpaid',
            ]);

            foreach ($data['items'] as $it) {
                $invoice->items()->create($it);
            }
        });

        return redirect()->route('invoices.index')->with('ok','Invoice created!');
    }
}
