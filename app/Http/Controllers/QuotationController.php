<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuotationController extends Controller
{
    public function index()
    {
        $quotes = Quotation::latest()->paginate(20);
        return view('quotations.index', compact('quotes'));
    }

    public function create()
    {
        return view('quotations.create'); // เราจะรี-ยูสหน้า invoice เดิม (ดูหัวข้อ 5)
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name'          => ['required','string','max:255'],
            'issue_date'             => ['required','date'],
            'valid_until'            => ['nullable','date','after_or_equal:issue_date'],
            'tax_rate'               => ['required','numeric','min:0'],

            'items'                  => ['required','array','min:1'],
            'items.*.description'    => ['required','string','max:255'],
            'items.*.qty'            => ['required','integer','min:1'],
            'items.*.price'          => ['required','numeric','min:0'],
            'items.*.unit'           => ['nullable','string','max:50'],
        ]);

        // clean rows
        $data['items'] = array_values(array_filter($data['items'], function ($row) {
            if (!is_array($row)) return false;
            $desc = trim((string)($row['description'] ?? ''));
            $qty  = (int)($row['qty'] ?? 0);
            $price= (float)($row['price'] ?? 0);
            return $desc !== '' && $qty > 0 && $price >= 0;
        }));
        if (!count($data['items'])) {
            return back()->withErrors(['items' => 'ต้องมีรายการอย่างน้อย 1 รายการ'])->withInput();
        }

        // calc
        $subtotal = 0.0;
        foreach ($data['items'] as &$it) {
            $line = round(((int)$it['qty']) * (float)$it['price'], 2);
            $it['line_total'] = $line;
            $subtotal += $line;
        }
        unset($it);

        $taxRate = (float)$data['tax_rate'];
        $tax   = round($subtotal * ($taxRate/100), 2);
        $total = round($subtotal + $tax, 2);

        // save
        $quote = DB::transaction(function () use ($data, $subtotal, $tax, $total, $taxRate) {
            // เลขรันรายวัน: QTYYYYMMDD-0001
            $prefix = 'QT'.now()->format('Ymd').'-';
            $lastNo = Quotation::where('number', 'like', $prefix.'%')->orderByDesc('id')->value('number');
            $running = ($lastNo && preg_match('/-(\d+)$/', $lastNo, $m)) ? ((int)$m[1] + 1) : 1;
            $number  = $prefix.str_pad((string)$running, 4, '0', STR_PAD_LEFT);

            $q = Quotation::create([
                'number'        => $number,
                'customer_name' => $data['customer_name'],
                'issue_date'    => $data['issue_date'],
                'valid_until'   => $data['valid_until'] ?? null,
                'tax_rate'      => $taxRate,
                'subtotal'      => $subtotal,
                'tax'           => $tax,
                'total'         => $total,
                'status'        => 'draft',
                // 'notes'       => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $row) {
                $q->items()->create([
                    'description' => $row['description'],
                    'qty'         => (int)$row['qty'],
                    'unit'        => $row['unit'] ?? null,
                    'price'       => (float)$row['price'],
                    'line_total'  => (float)$row['line_total'],
                ]);
            }

            return $q;
        });

        return redirect()->route('quotes.index')->with('ok', "บันทึกใบเสนอราคาเลขที่ {$quote->number} แล้ว");
    }

    public function edit(Quotation $quote)
    {
        $quote->load('items');
        return view('quotations.edit', compact('quote')); // รี-ยูสฟอร์มเดิมได้เหมือนกัน
    }

    public function update(Request $request, Quotation $quote)
    {
        // (ทำเหมือน store ได้เลย – ถ้าหนิงอยากอัปเดตภายหลังบอกได้ เดี๋ยวจัดให้ครบ)
        return back()->with('ok','ยังไม่เปิดใช้ update (เดี๋ยวเติมให้ได้)');
    }

    // (optional) show
    public function show(Quotation $quote)
    {
        $quote->load('items');
        return view('quotations.show', compact('quote'));
    }
}
