<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use RuntimeException;

class InvoiceFromQuotation
{
    public function convert(Quotation $q): Invoice
    {
        return DB::transaction(function () use ($q) {
            // ล็อกกันกดซ้ำ
            $q = Quotation::query()->whereKey($q->getKey())->lockForUpdate()->first();

            if (($q->status ?? 'draft') !== 'approved') {
                throw new RuntimeException('Quotation must be approved first.');
            }

            // ถ้ามี relation invoice() และมีอยู่แล้วก็คืนใบเดิม (กันสร้างซ้ำ)
            if (method_exists($q, 'invoice') && $q->invoice) {
                return $q->invoice->fresh('items');
            }

            // === ออกเลข INV ตามเดือน issue_date: INVYYYY-MM-XXXX ===
            $period = ($q->issue_date ?: now())->format('Y-m');
            $prefix = 'INV'.$period.'-';
            $lastNo = Invoice::where('number','like',$prefix.'%')->orderByDesc('id')->value('number');
            $seq = 0;
            if ($lastNo && preg_match('/-(\d{4})$/', $lastNo, $m)) $seq = (int)$m[1];
            $seq++;
            $invNo = $prefix.str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

            // === สร้างหัวใบแจ้งหนี้ (ตาม schema ของหนิง) ===
            $inv = new Invoice();
            $inv->number        = $invNo;                                     // string (nullable ใน schema แต่เรากรอกให้)
            $inv->customer_name = $q->customer_name;                           // string
            $inv->issue_date    = ($q->issue_date ?: now())->toDateString();   // date
            $inv->due_date      = Carbon::parse($inv->issue_date)->addDays(14)->toDateString(); // date|null
            $inv->tax_rate      = (float)($q->tax_rate ?? 0);                  // decimal(5,2)
            $inv->subtotal      = 0;                                           // decimal(12,2)
            $inv->tax           = 0;
            $inv->total         = 0;
            $inv->status        = 'unpaid';                                    // string default
            $inv->save();

            // === รายการสินค้า ===
            $q->loadMissing('items');
            foreach ($q->items as $it) {
                $qty   = (float)($it->qty ?? $it->quantity ?? 0);
                $price = (float)($it->price ?? $it->unit_price ?? 0);
                $disc  = (float)($it->discount ?? 0);
                $line  = round(($qty * $price) - $disc, 2);

                $inv->items()->create([
                    'description' => (string)($it->description ?? ''),
                    'qty'         => (int) round($qty), // ตารางของหนิงเป็น integer
                    'unit'        => $it->unit ?? null, // มีคอลัมน์ unit แล้วจาก migration 2025_10_08...
                    'price'       => $price,
                    'line_total'  => $line,
                ]);
            }

            // === คำนวณยอดรวม ===
            $inv->loadMissing('items');
            $subtotal = (float) $inv->items->sum('line_total');
            $tax = round($subtotal * ((float)$inv->tax_rate/100), 2);
            $total = round($subtotal + $tax, 2);

            $inv->update(compact('subtotal','tax','total'));

            return $inv->fresh('items');
        });
    }
}
