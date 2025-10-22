<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\InvoiceFromQuotation;

class QuotationController extends Controller
{
    /**
     * แสดงรายการ Quotation + ค้นหา
     */
    public function index(Request $request)
    {
        $q = $request->query('q'); // คำค้นจากกล่อง search

        $dateCol = Schema::hasColumn('quotations','issue_date')
            ? 'issue_date'
            : (Schema::hasColumn('quotations','quoted_at') ? 'quoted_at' : 'created_at');

        $quotes = Quotation::query()
            ->select('id','number','customer_name','status','total', $dateCol.' as d')
            ->when(filled($q), function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('number', 'like', "%{$q}%")
                      ->orWhere('customer_name', 'like', "%{$q}%")
                      ->orWhere('status', 'like', "%{$q}%")
                      ->orWhere('total', 'like', "%{$q}%");
                });
            })
            ->orderByDesc($dateCol)
            ->paginate(15)
            ->withQueryString(); // ให้ pagination พกพา ?q= ไปด้วย

        return view('quotations.index', [
            'quotes' => $quotes,
            'q'      => $q,
        ]);
    }

    /**
     * เมทาดาต้าสำหรับฟอร์ม (ใช้ได้ทั้ง create/edit)
     */
    private function formMeta(): array
    {
        return [
            'statuses' => [
                'draft'     => 'Draft',
                'approved'  => 'Approved',
                'rejected'  => 'Rejected',
            ],
            'currencies' => [
                'THB' => 'THB (฿)',
                'USD' => 'USD ($)',
            ],
            'branchTypes' => [
                '-'      => '—',
                'head'   => 'Head',
                'branch' => 'Branch',
            ],
        ];
    }

    public function create()
    {
        // โมเดลว่างพร้อมค่า default ให้ฟอร์มเติมอัตโนมัติ
        $q = new Quotation([
            'issue_date'           => now()->toDateString(),
            'valid_until'          => null,
            'status'               => 'draft',
            'currency'             => 'THB',
            'tax_rate'             => 0,
            'discount_percent'     => 0,
            'vat_enabled'          => false,
            'customer_branch_type' => '-', // ให้ตรงกับฟอร์ม
        ]);

        // แสดงตัวอย่างเลข (ตัวจริงจะออกตอนบันทึก)
        $q->number = 'QT'.now()->format('Y-m').'-????';
        $provisionalNumber = $q->number; // เผื่อวิวเก่าอ้างตัวแปรนี้

        return view(
            'quotations.create',
            $this->formMeta() + [
                'quotation'          => $q,
                'provisionalNumber'  => $provisionalNumber,
            ]
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'number'               => ['prohibited'], // ออกเลขใน Model เอง
            'customer_name'        => ['required','string','max:255'],
            'issue_date'           => ['nullable','date'],
            'valid_until'          => ['nullable','date','after_or_equal:issue_date'],
            'currency'             => ['nullable','string','max:10'],
            'customer_address'     => ['nullable','string'],
            'customer_tax_id'      => ['nullable','string','max:50'],
            'customer_branch_type' => ['nullable','in:head,branch,-'],
            'customer_branch_code' => ['nullable','string','max:50'],
            'salesperson'          => ['nullable','string','max:255'],
            'reference'            => ['nullable','string','max:255'],
            'discount_percent'     => ['nullable','numeric','min:0'],
            'vat_enabled'          => ['nullable'],
            'tax_rate'             => ['nullable','numeric','min:0','max:100'],
            'subtotal'             => ['nullable','numeric'],
            'discount_amount'      => ['nullable','numeric'],
            'tax'                  => ['nullable','numeric'],
            'total'                => ['nullable','numeric'],
            // รายการ (รองรับชื่อสองแบบ)
            'items'                 => ['array'],
            'items.*.description'   => ['nullable','string'],
            'items.*.quantity'      => ['nullable','numeric','min:0'],
            'items.*.unit_price'    => ['nullable','numeric','min:0'],
            'items.*.qty'           => ['nullable','numeric','min:0'],
            'items.*.price'         => ['nullable','numeric','min:0'],
            'items.*.discount'      => ['nullable','numeric','min:0'],
            'items.*.unit'          => ['nullable','string','max:50'],
        ]);

        $payload = [
            'customer_name' => $data['customer_name'],
            'status'        => 'draft',
        ];
        foreach ([
            'issue_date','valid_until','currency','customer_address','customer_tax_id',
            'customer_branch_type','customer_branch_code','salesperson','reference',
            'discount_percent','tax_rate','subtotal','discount_amount','tax','total'
        ] as $col) {
            if (Schema::hasColumn('quotations', $col) && array_key_exists($col,$data)) {
                $payload[$col] = $data[$col];
            }
        }
        if (Schema::hasColumn('quotations','vat_enabled')) {
            $payload['vat_enabled'] = $request->filled('vat_enabled');
        }
        if (Schema::hasColumn('quotations','issue_date') && empty($payload['issue_date'])) {
            $payload['issue_date'] = now();
        }

        $quotation = DB::transaction(function () use ($payload, $data) {
            /** @var \App\Models\Quotation $q */
            $q = Quotation::create($payload); // Model จะ assign number เองตอน creating()

            if (method_exists($q, 'items')) {
                $rows = collect($data['items'] ?? [])
                    ->map(fn($r) => $this->normalizeItemRow($r))
                    ->filter(fn($r) => $r['description'] !== '' || $r['qty'] > 0 || $r['price'] > 0);

                foreach ($rows as $row) {
                    $q->items()->create([
                        'description' => $row['description'],
                        'qty'         => $row['qty'],
                        'quantity'    => $row['qty'],
                        'unit_price'  => $row['price'],
                        'price'       => $row['price'],
                        'discount'    => $row['discount'],
                        'line_total'  => round(($row['qty'] * $row['price']) - $row['discount'], 2),
                        'unit'        => $row['unit'],
                    ]);
                }
            }

            $this->recalculateHead($q);
            return $q;
        });

        return redirect()->route('quotations.show', $quotation)->with('ok', 'Created.');
    }

    public function show(Quotation $quotation)
    {
        if (method_exists($quotation, 'items')) {
            $quotation->load('items');
        }
        return view('quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        if (method_exists($quotation,'items')) {
            $quotation->load(['items' => fn($q) => $q->orderBy('id')]);
        }

        // ส่ง option ไปให้เหมือนหน้า create เพื่อให้ฟอร์มขึ้นครบ
        return view(
            'quotations.edit',
            $this->formMeta() + [
                'quotation' => $quotation,
            ]
        );
    }

    public function update(Request $request, Quotation $quotation)
    {
        $data = $request->validate([
            'number'               => ['prohibited'],
            'customer_name'        => ['required','string','max:255'],
            'issue_date'           => ['required','date'],
            'valid_until'          => ['nullable','date','after_or_equal:issue_date'],
            'currency'             => ['nullable','string','max:10'],
            'customer_address'     => ['nullable','string'],
            'customer_tax_id'      => ['nullable','string','max:50'],
            'customer_branch_type' => ['nullable','in:head,branch,-'],
            'customer_branch_code' => ['nullable','string','max:50'],
            'salesperson'          => ['nullable','string','max:255'],
            'reference'            => ['nullable','string','max:255'],
            'discount_percent'     => ['nullable','numeric','min:0'],
            'vat_enabled'          => ['nullable'],
            'tax_rate'             => ['nullable','numeric','min:0','max:100'],
            'status'               => ['required','in:draft,approved,rejected'],
            // รายการ
            'items'                 => ['required','array','min:1'],
            'items.*.description'   => ['nullable','string'],
            'items.*.quantity'      => ['nullable','numeric','min:0'],
            'items.*.unit_price'    => ['nullable','numeric','min:0'],
            'items.*.qty'           => ['nullable','numeric','min:0'],
            'items.*.price'         => ['nullable','numeric','min:0'],
            'items.*.discount'      => ['nullable','numeric','min:0'],
            'items.*.unit'          => ['nullable','string','max:50'],
        ]);

        DB::transaction(function () use ($quotation, $request, $data) {
            $payload = [
                'customer_name' => $data['customer_name'],
                'issue_date'    => $data['issue_date'],
                'status'        => $data['status'],
            ];
            foreach ([
                'valid_until','currency','customer_address','customer_tax_id',
                'customer_branch_type','customer_branch_code','salesperson','reference',
                'discount_percent','tax_rate'
            ] as $col) {
                if (Schema::hasColumn('quotations',$col) && array_key_exists($col,$data)) {
                    $payload[$col] = $data[$col];
                }
            }
            if (Schema::hasColumn('quotations','vat_enabled')) {
                $payload['vat_enabled'] = $request->filled('vat_enabled');
            }
            $quotation->update($payload);

            // อัปเดตรายการ: ลบทิ้งแล้วสร้างใหม่
            if (method_exists($quotation,'items')) {
                $quotation->items()->delete();

                $rows = collect($data['items'])
                    ->map(fn($r) => $this->normalizeItemRow($r))
                    ->filter(fn($r) => $r['description'] !== '' || $r['qty'] > 0 || $r['price'] > 0);

                foreach ($rows as $row) {
                    $quotation->items()->create([
                        'description' => $row['description'],
                        'qty'         => $row['qty'],
                        'quantity'    => $row['qty'],
                        'unit_price'  => $row['price'],
                        'price'       => $row['price'],
                        'discount'    => $row['discount'],
                        'line_total'  => round(($row['qty'] * $row['price']) - $row['discount'], 2),
                        'unit'        => $row['unit'],
                    ]);
                }
            }

            $this->recalculateHead($quotation);
        });

        // ถ้าเปลี่ยนเดือน เลขอาจเปลี่ยน → refresh
        $quotation->refresh();

        // ถ้า Approved → แปลงเป็น Invoice
        if (($quotation->status ?? 'draft') === 'approved') {
            $invoice = null;

            try {
                if (class_exists(\App\Services\InvoiceFromQuotation::class)) {
                    $invoice = app(\App\Services\InvoiceFromQuotation::class)->convert($quotation);
                }
            } catch (\Throwable $e) {
                Log::error('Service convert QT->INV failed: '.$e->getMessage());
                $invoice = null;
            }

            if (!$invoice) {
                try {
                    $invoice = $this->inlineCreateInvoice($quotation);
                } catch (\Throwable $e) {
                    Log::error('Inline create invoice failed: '.$e->getMessage());
                    $invoice = null;
                }
            }

            if ($invoice) {
                return redirect()
                    ->route('invoices.show', $invoice)
                    ->with('ok', 'Invoice created from '.$quotation->number.' ✅');
            }

            return redirect()
                ->route('quotations.index')
                ->with('ok', 'Approved: '.$quotation->number.' (Could not auto-create invoice)');
        }

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('ok','Updated.');
    }

    public function destroy(Quotation $quotation)
    {
        DB::transaction(function () use ($quotation) {
            if (method_exists($quotation,'items')) {
                $quotation->items()->delete();
            }
            $quotation->delete();
        });

        return back()->with('ok','Deleted.');
    }

    /** ----------------- Helpers ----------------- */

    private function normalizeItemRow(array $r): array
    {
        $qty   = (float)($r['qty'] ?? $r['quantity'] ?? 0);
        $price = (float)($r['price'] ?? $r['unit_price'] ?? 0);
        $disc  = (float)($r['discount'] ?? 0);

        return [
            'description' => (string)($r['description'] ?? ''),
            'qty'         => $qty,
            'price'       => $price,
            'discount'    => $disc,
            'unit'        => $r['unit'] ?? null,
        ];
    }

    private function recalculateHead(Quotation $q): void
    {
        $q->loadMissing('items');

        $subtotal = $q->items->sum(function ($i) {
            $qty   = (float)($i->qty ?? $i->quantity ?? 0);
            $price = (float)($i->price ?? $i->unit_price ?? 0);
            $disc  = (float)($i->discount ?? 0);
            return ($qty * $price) - $disc;
        });

        $taxRate = (float)($q->tax_rate ?? 0);
        $tax = $taxRate > 0 ? round($subtotal * ($taxRate/100), 2) : 0.0;
        $total = round($subtotal + $tax, 2);

        $payload = [];
        if (Schema::hasColumn('quotations','subtotal')) $payload['subtotal'] = $subtotal;
        if (Schema::hasColumn('quotations','tax'))      $payload['tax']      = $tax;
        if (Schema::hasColumn('quotations','total'))    $payload['total']    = $total;

        if ($payload) {
            $q->update($payload);
        }
    }

    /**
     * Fallback: สร้าง Invoice จาก Quotation ตรง ๆ (ไม่พึ่ง service, ไม่ติด fillable)
     */
    private function inlineCreateInvoice(Quotation $q): ?Invoice
    {
        if (method_exists($q, 'invoice') && $q->invoice) {
            return $q->invoice->fresh('items');
        }

        return DB::transaction(function () use ($q) {
            $q->loadMissing('items');

            // ออกเลข INV ตามเดือน issue_date: INVYYYY-MM-XXXX
            $period = ($q->issue_date ?: now())->format('Y-m');
            $prefix = 'INV'.$period.'-';
            $lastNo = Invoice::where('number','like',$prefix.'%')->orderByDesc('id')->value('number');
            $seq = 0;
            if ($lastNo && preg_match('/-(\d{4})$/', $lastNo, $m)) $seq = (int)$m[1];
            $seq++;
            $invNo = $prefix.str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

            // หัวใบแจ้งหนี้
            $inv = new Invoice();
            $inv->number        = $invNo;
            $inv->customer_name = (string) $q->customer_name;
            $inv->issue_date    = ($q->issue_date ?: now())->toDateString();
            $inv->due_date      = \Illuminate\Support\Carbon::parse($inv->issue_date)->addDays(14)->toDateString();
            $inv->tax_rate      = (float) ($q->tax_rate ?? 0);
            $inv->subtotal      = 0;
            $inv->tax           = 0;
            $inv->total         = 0;
            $inv->status        = 'unpaid';
            $inv->save();

            // รายการ
            $subtotal = 0.0;
            foreach ($q->items as $it) {
                $qty   = (float)($it->qty ?? $it->quantity ?? 0);
                $price = (float)($it->price ?? $it->unit_price ?? 0);
                $disc  = (float)($it->discount ?? 0);
                $line  = round(($qty * $price) - $disc, 2);

                $item = new InvoiceItem();
                $item->invoice_id = $inv->id;
                $item->description= (string)($it->description ?? '');
                $item->qty        = (int) round($qty);
                if (Schema::hasColumn('invoice_items','unit')) {
                    $item->unit   = $it->unit ?? null;
                }
                $item->price      = $price;
                $item->line_total = $line;
                $item->save();

                $subtotal += $line;
            }

            // รวมยอด
            $tax   = round($subtotal * ((float)$inv->tax_rate/100), 2);
            $total = round($subtotal + $tax, 2);

            $inv->subtotal = $subtotal;
            $inv->tax      = $tax;
            $inv->total    = $total;
            $inv->save();

            return $inv->fresh('items');
        });
    }
}
