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
        // 1) validate
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
            // 'notes'                => ['nullable','string'],
        ]);

        // 2) ลบแถวว่าง
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

        // 3) คำนวณยอด
        $subtotal = 0.0;
        foreach ($data['items'] as &$it) {
            $qty   = (int)$it['qty'];
            $price = (float)$it['price'];
            $line  = round($qty * $price, 2);
            $it['line_total'] = $line;
            $subtotal += $line;
        }
        unset($it);

        $taxRate = (float)$data['tax_rate'];
        $afterDisc = $subtotal; // (ตอนนี้ยังไม่มีส่วนลดฝั่ง backend)
        $tax   = round($afterDisc * ($taxRate / 100), 2);
        $total = round($afterDisc + $tax, 2);

        // 4) บันทึก
        $invoice = DB::transaction(function () use ($data, $subtotal, $tax, $total, $taxRate) {
            // เลขรันรายวัน: INVYYYYMMDD-0001
            $prefix = 'INV'.now()->format('Ymd').'-';
            $lastNo = Invoice::where('number', 'like', $prefix.'%')
                        ->orderByDesc('id')->value('number');
            $running = 1;
            if ($lastNo && preg_match('/-(\d+)$/', $lastNo, $m)) {
                $running = (int)$m[1] + 1;
            }
            $number = $prefix.str_pad((string)$running, 4, '0', STR_PAD_LEFT);

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
                // 'notes'       => $data['notes'] ?? null,
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

        return redirect()
            ->route('invoices.index')
            ->with('ok', "บันทึกใบแจ้งหนี้เลขที่ {$invoice->number} แล้ว");
    }
}
