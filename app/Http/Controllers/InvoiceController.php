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
            'items.*.unit'           => ['nullable','string','max:50'],
        ]);

        // ตัดแถวว่าง
        $data['items'] = array_values(array_filter($data['items'], function ($row) {
            if (!is_array($row)) return false;
            $desc = trim((string)($row['description'] ?? ''));
            $qty  = (int)($row['qty'] ?? 0);
            $price= (float)($row['price'] ?? 0);
            return $desc !== '' && $qty > 0 && $price >= 0;
        }));
        if (count($data['items']) === 0) {
            return back()->withErrors(['items' => 'ต้องมีรายการสินค้าอย่างน้อย 1 รายการ'])->withInput();
        }

        // คำนวณ
        $subtotal = 0.0;
        foreach ($data['items'] as &$it) {
            $line = (int)$it['qty'] * (float)$it['price'];
            $it['line_total'] = round($line, 2);
            $subtotal += $it['line_total'];
        }
        unset($it);

        $taxRate = (float)$data['tax_rate'];
        $tax     = round($subtotal * ($taxRate / 100), 2);
        $total   = round($subtotal + $tax, 2);

        // บันทึก
        $invoice = DB::transaction(function () use ($data, $subtotal, $tax, $total, $taxRate) {
            $prefix = 'INV'.now()->format('Ymd').'-';
            $lastNo = Invoice::where('number','like',$prefix.'%')->orderByDesc('id')->value('number');
            $run = 1;
            if ($lastNo && preg_match('/-(\d+)$/', $lastNo, $m)) $run = (int)$m[1] + 1;
            $number = $prefix.str_pad((string)$run, 4, '0', STR_PAD_LEFT);

            $invoice = Invoice::create([
                'number'        => $number,
                'customer_name' => $data['customer_name'],
                'issue_date'    => $data['issue_date'],
                'due_date'      => $data['due_date'] ?? null,
                'tax_rate'      => $taxRate,
                'subtotal'      => $subtotal,
                'tax'           => $tax,
                'total'         => $total,
                'status'        => 'unpaid',
            ]);

            foreach ($data['items'] as $row) {
                $invoice->items()->create([
                    'description' => $row['description'],
                    'qty'         => (int)$row['qty'],
                    'price'       => (float)$row['price'],
                    'line_total'  => (float)$row['line_total'],
                    'unit'        => $row['unit'] ?? null,
                ]);
            }

            return $invoice;
        });

        return redirect()->route('invoices.index')
            ->with('ok', "บันทึกใบแจ้งหนี้เลขที่ {$invoice->number} แล้ว");
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('items');
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load('items');
        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
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

        $data['items'] = array_values(array_filter($data['items'], function ($row) {
            if (!is_array($row)) return false;
            $desc = trim((string)($row['description'] ?? ''));
            $qty  = (int)($row['qty'] ?? 0);
            $price= (float)($row['price'] ?? 0);
            return $desc !== '' && $qty > 0 && $price >= 0;
        }));

        $subtotal = 0.0;
        foreach ($data['items'] as &$it) {
            $line = (int)$it['qty'] * (float)$it['price'];
            $it['line_total'] = round($line, 2);
            $subtotal += $it['line_total'];
        }
        unset($it);

        $taxRate = (float)$data['tax_rate'];
        $tax     = round($subtotal * ($taxRate/100), 2);
        $total   = round($subtotal + $tax, 2);

        DB::transaction(function () use ($invoice, $data, $taxRate, $subtotal, $tax, $total) {
            $invoice->update([
                'customer_name' => $data['customer_name'],
                'issue_date'    => $data['issue_date'],
                'due_date'      => $data['due_date'] ?? null,
                'tax_rate'      => $taxRate,
                'subtotal'      => $subtotal,
                'tax'           => $tax,
                'total'         => $total,
            ]);

            // ลบของเดิมแล้วเพิ่มใหม่แบบง่าย ๆ
            $invoice->items()->delete();
            foreach ($data['items'] as $row) {
                $invoice->items()->create([
                    'description' => $row['description'],
                    'qty'         => (int)$row['qty'],
                    'price'       => (float)$row['price'],
                    'line_total'  => (float)$row['line_total'],
                    'unit'        => $row['unit'] ?? null,
                ]);
            }
        });

        return redirect()->route('invoices.show', $invoice)->with('ok','อัปเดตเรียบร้อย');
    }
}
