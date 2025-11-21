<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PurchaseOrder;
use App\Models\QuotationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Support\SimplePdf;
use App\Services\InvoiceFromQuotation;
use Dompdf\Dompdf;
use Dompdf\Options;

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
                'sent'      => 'Sent',
                'approved'  => 'Approved',
                'rejected'  => 'Rejected',
                'cancelled' => 'Cancelled',
            ],
            'currencies' => [
                'THB' => 'THB (฿)',
                'USD' => 'USD ($)',
                'EUR' => 'EUR (€)',
                'JPY' => 'JPY (¥)',
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
            'tax_rate'             => 7,
            'vat_mode'             => 'exclusive',
            'discount_percent'     => 0,
            'discount_amount'      => 0,
            'vat_enabled'          => true,
            'customer_branch_type' => '-', // ให้ตรงกับฟอร์ม
        ]);

        // แสดงตัวอย่างเลข (ตัวจริงจะออกตอนบันทึก)
        $provisionalNumber = Quotation::previewNextNumber();
        $q->number = $provisionalNumber;

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
            'number'               => ['nullable','string','max:50','unique:quotations,number'],
            'customer_id'          => ['nullable','integer','exists:customers,id'],
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
            'payment_terms'        => ['nullable','string','max:255'],
            'contact_name'         => ['nullable','string','max:255'],
            'contact_email'        => ['nullable','string','max:255'],
            'contact_phone'        => ['nullable','string','max:255'],
            'discount_percent'     => ['nullable','numeric','min:0'],
            'discount_amount'      => ['nullable','numeric','min:0'],
            'vat_enabled'          => ['nullable'],
            'vat_mode'             => ['nullable','in:exclusive,inclusive,none'],
            'tax_rate'             => ['nullable','numeric','min:0','max:100'],
            'subtotal'             => ['nullable','numeric'],
            'tax'                  => ['nullable','numeric'],
            'total'                => ['nullable','numeric'],
            'status'               => ['nullable','in:draft,sent,approved,rejected,cancelled'],
            // รายการ (รองรับชื่อสองแบบ)
            'items'                 => ['array'],
            'items.*.description'   => ['nullable','string'],
            'items.*.quantity'      => ['nullable','numeric','min:0'],
            'items.*.unit_price'    => ['nullable','numeric','min:0'],
            'items.*.qty'           => ['nullable','numeric','min:0'],
            'items.*.price'         => ['nullable','numeric','min:0'],
            'items.*.discount'      => ['nullable','numeric','min:0'],
            'items.*.unit'          => ['nullable','string','max:50'],
            'attachments'           => ['sometimes','array'],
            'attachments.*'         => ['file','max:12288','mimes:pdf,jpeg,jpg,png,webp,doc,docx'],
        ]);

        $payload = [
            'customer_name' => $data['customer_name'],
            'status'        => $data['status'] ?? 'draft',
        ];
        if (!empty($data['number'])) {
            $payload['number'] = $data['number'];
        }
        foreach ([
            'issue_date','valid_until','currency','customer_address','customer_tax_id','customer_id',
            'customer_branch_type','customer_branch_code','salesperson','reference','payment_terms',
            'contact_name','contact_email','contact_phone','vat_mode',
            'discount_percent','discount_amount','tax_rate','withholding_rate','subtotal','tax','total',
            'status'
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

        $quotation = DB::transaction(function () use ($payload, $data, $request) {
            /** @var \App\Models\Quotation $q */
            $q = Quotation::create($payload); // Model จะ assign number เองตอน creating()

            if (method_exists($q, 'items')) {
                $rows = collect($data['items'] ?? [])
                    ->map(fn($r) => $this->normalizeItemRow($r))
                    ->filter(fn($r) => $r['description'] !== '' || $r['qty'] > 0 || $r['price'] > 0 || $r['discount'] > 0);

                foreach ($rows as $row) {
                    $lineTotal = round(($row['qty'] * $row['price']) - $row['discount'], 2);
                    $attributes = [
                        'description' => $row['description'],
                        'qty'         => $row['qty'],
                        'quantity'    => $row['qty'],
                        'unit_price'  => $row['price'],
                        'price'       => $row['price'],
                        'line_total'  => $lineTotal,
                        'unit'        => $row['unit'],
                    ];

                    if (Schema::hasColumn('quote_items', 'discount')) {
                        $attributes['discount'] = $row['discount'];
                    }

                    $q->items()->create($attributes);
                }
            }

            $this->recalculateHead($q);
            $this->storeAttachments($q, $request->file('attachments', []));
            return $q;
        });

        $this->logAction($quotation, 'created', 'Created quotation '.$quotation->number);

        return redirect()->route('quotations.show', $quotation)->with('ok', 'Created.');
    }

    public function show(Quotation $quotation)
    {
        if (method_exists($quotation, 'items')) {
            $quotation->load('items');
        }
        return view('quotations.show', compact('quotation'));
    }

    public function pdf(Quotation $quotation)
    {
        $quotation->loadMissing(['items' => fn ($q) => $q->orderBy('id')]);

        // ใช้ Dompdf ถ้ามี หากไม่มีให้ fallback เป็นตัวสร้าง PDF ในบ้านเพื่อกัน 500
        if (class_exists(Dompdf::class)) {
            $html = view('quotations.pdf', [
                'quotation' => $quotation,
            ])->render();

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
            // fallback ง่าย ๆ: แปลงข้อมูลหัว/รายการเป็น PDF ข้อความเพื่อให้โหลดไฟล์ได้
            $payload = SimplePdf::quotation($quotation);
        }

        $filename = ($quotation->number ?? 'quotation').'.pdf';

        // เขียนไฟล์ลง storage แล้วเสิร์ฟด้วย response()->file เพื่อ header ถูกต้อง
        $dir = storage_path('app/quotations');
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

    public function convertToInvoice(Quotation $quotation)
    {
        $quotation->loadMissing('items');
        $invoice = null;
        try {
            if (class_exists(\App\Services\InvoiceFromQuotation::class)) {
                $invoice = app(\App\Services\InvoiceFromQuotation::class)->convert($quotation);
            }
        } catch (\Throwable $e) {
            Log::error('Service convert QT->INV failed: '.$e->getMessage());
        }

        if (!$invoice) {
            $invoice = $this->inlineCreateInvoice($quotation);
        }

        if ($invoice) {
            if (Schema::hasColumn('invoices', 'quotation_number') && empty($invoice->quotation_number)) {
                $invoice->quotation_number = $quotation->number ?? ('QT'.$quotation->id);
                $invoice->save();
            }
            $this->logAction($quotation, 'converted_to_invoice', 'Converted to invoice '.$invoice->number);
            return redirect()->route('invoices.show', $invoice)->with('ok','Invoice created from quotation.');
        }

        return back()->with('error','ไม่สามารถสร้าง Invoice ได้');
    }

    public function convertToPo(Quotation $quotation)
    {
        $quotation->loadMissing('items');
        $po = $this->inlineCreatePurchaseOrder($quotation);
        if ($po) {
            $this->logAction($quotation, 'converted_to_po', 'Converted to PO '.$po->number);
            return redirect()->route('po.show', $po)->with('ok','สร้าง PO จาก Quotation แล้ว');
        }
        return back()->with('error','ไม่สามารถสร้าง PO ได้');
    }

    public function copy(Quotation $quotation)
    {
        $quotation->loadMissing(['items','attachments']);

        $duplicate = DB::transaction(function () use ($quotation) {
            $new = $quotation->replicate();
            $new->number = null;
            $new->period = null;
            $new->month_seq = null;
            $new->status = 'draft';
            $new->issue_date = now();
            $new->save();

            if (method_exists($quotation, 'items')) {
                foreach ($quotation->items as $item) {
                    $attributes = [
                        'description' => (string) ($item->description ?? ''),
                        'qty'         => (float) ($item->qty ?? $item->quantity ?? 0),
                        'quantity'    => (float) ($item->qty ?? $item->quantity ?? 0),
                        'unit_price'  => (float) ($item->price ?? $item->unit_price ?? 0),
                        'price'       => (float) ($item->price ?? $item->unit_price ?? 0),
                        'line_total'  => (float) ($item->line_total ?? 0),
                        'unit'        => $item->unit ?? null,
                    ];

                    if (Schema::hasColumn('quote_items', 'discount')) {
                        $attributes['discount'] = (float) ($item->discount ?? 0);
                    }

                    $new->items()->create($attributes);
                }
            }

            if (method_exists($quotation, 'attachments')) {
                foreach ($quotation->attachments as $att) {
                    $new->attachments()->create([
                        'path'          => $att->path,
                        'original_name' => $att->original_name,
                        'mime_type'     => $att->mime_type,
                        'size'          => $att->size,
                    ]);
                }
            }

            $this->recalculateHead($new);

            return $new;
        });

        $this->logAction($duplicate, 'copied', 'Copied from '.$quotation->number.' to '.$duplicate->number);

        return redirect()->route('quotations.edit', $duplicate)->with('ok', 'Copied from '.$quotation->number);
    }

    public function edit(Quotation $quotation)
    {
        if (method_exists($quotation,'items')) {
            $quotation->load(['items' => fn($q) => $q->orderBy('id')]);
        }

        // ใช้เลย์เอาต์เดียวกับหน้า create เพื่อให้ UI สอดคล้อง
        return view(
            'quotations.create',
            $this->formMeta() + [
                'quotation' => $quotation,
            ]
        );
    }

    public function update(Request $request, Quotation $quotation)
    {
        $data = $request->validate([
            'number'               => ['nullable','string','max:50', Rule::unique('quotations','number')->ignore($quotation->id)],
            'customer_id'          => ['nullable','integer','exists:customers,id'],
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
            'payment_terms'        => ['nullable','string','max:255'],
            'contact_name'         => ['nullable','string','max:255'],
            'contact_email'        => ['nullable','string','max:255'],
            'contact_phone'        => ['nullable','string','max:255'],
            'discount_percent'     => ['nullable','numeric','min:0'],
            'discount_amount'      => ['nullable','numeric','min:0'],
            'vat_enabled'          => ['nullable'],
            'vat_mode'             => ['nullable','in:exclusive,inclusive,none'],
            'tax_rate'             => ['nullable','numeric','min:0','max:100'],
            'withholding_rate'     => ['nullable','numeric','min:0','max:100'],
            'status'               => ['required','in:draft,sent,approved,rejected,cancelled'],
            // รายการ
            'items'                 => ['required','array','min:1'],
            'items.*.description'   => ['nullable','string'],
            'items.*.quantity'      => ['nullable','numeric','min:0'],
            'items.*.unit_price'    => ['nullable','numeric','min:0'],
            'items.*.qty'           => ['nullable','numeric','min:0'],
            'items.*.price'         => ['nullable','numeric','min:0'],
            'items.*.discount'      => ['nullable','numeric','min:0'],
            'items.*.unit'          => ['nullable','string','max:50'],
            'attachments'           => ['sometimes','array'],
            'attachments.*'         => ['file','max:12288','mimes:pdf,jpeg,jpg,png,webp,doc,docx'],
        ]);

        DB::transaction(function () use ($quotation, $request, $data) {
            $payload = [
                'customer_name' => $data['customer_name'],
                'issue_date'    => $data['issue_date'],
                'status'        => $data['status'],
            ];
            if (!empty($data['number'])) {
                $payload['number'] = $data['number'];
            }
            foreach ([
                'valid_until','currency','customer_address','customer_tax_id','customer_id',
                'customer_branch_type','customer_branch_code','salesperson','reference','payment_terms',
                'contact_name','contact_email','contact_phone','vat_mode',
                'discount_percent','discount_amount','tax_rate','withholding_rate'
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
                    ->filter(fn($r) => $r['description'] !== '' || $r['qty'] > 0 || $r['price'] > 0 || $r['discount'] > 0);

                foreach ($rows as $row) {
                    $lineTotal = round(($row['qty'] * $row['price']) - $row['discount'], 2);
                    $attributes = [
                        'description' => $row['description'],
                        'qty'         => $row['qty'],
                        'quantity'    => $row['qty'],
                        'unit_price'  => $row['price'],
                        'price'       => $row['price'],
                        'line_total'  => $lineTotal,
                        'unit'        => $row['unit'],
                    ];

                    if (Schema::hasColumn('quote_items', 'discount')) {
                        $attributes['discount'] = $row['discount'];
                    }

                    $quotation->items()->create($attributes);
                }
            }

            $this->recalculateHead($quotation);
            $this->storeAttachments($quotation, $request->file('attachments', []));
        });

        // ถ้าเปลี่ยนเดือน เลขอาจเปลี่ยน → refresh
        $quotation->refresh();

        $this->logAction(
            $quotation,
            'updated',
            'Updated quotation (status: '.($quotation->status ?? 'draft').', total: '.number_format((float)($quotation->total ?? 0), 2).')'
        );

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
        $this->logAction($quotation, 'deleted', 'Deleted quotation '.$quotation->number);
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

        $lineSubtotal = $q->items->sum(function ($i) {
            $qty   = (float)($i->qty ?? $i->quantity ?? 0);
            $price = (float)($i->price ?? $i->unit_price ?? 0);
            $disc  = (float)($i->discount ?? 0);
            return ($qty * $price) - $disc;
        });

        $discPct = max(0, (float)($q->discount_percent ?? 0));
        $discAmt = max(0, (float)($q->discount_amount ?? 0));
        $docDiscount = ($lineSubtotal * ($discPct/100)) + $discAmt;

        $base = max($lineSubtotal - $docDiscount, 0);
        $taxRate = (float)($q->tax_rate ?? 0);
        $mode = $q->vat_mode ?? 'exclusive';
        $taxEnabled = (bool)($q->vat_enabled ?? false) && $taxRate > 0 && $mode !== 'none';

        $tax = 0.0;
        $subtotal = $base;
        $total = $base;

        if ($taxEnabled) {
            if ($mode === 'inclusive') {
                $net = $base / (1 + ($taxRate/100));
                $tax = round($base - $net, 2);
                $subtotal = round($net, 2);
                $total = round($base, 2);
            } else { // exclusive
                $tax = round($base * ($taxRate/100), 2);
                $subtotal = round($base, 2);
                $total = round($base + $tax, 2);
            }
        } else {
            $subtotal = round($base, 2);
            $total = $subtotal;
        }

        $payload = [];
        if (Schema::hasColumn('quotations','discount_amount')) $payload['discount_amount'] = $docDiscount;
        if (Schema::hasColumn('quotations','subtotal')) $payload['subtotal'] = $subtotal;
        if (Schema::hasColumn('quotations','tax'))      $payload['tax']      = $tax;
        if (Schema::hasColumn('quotations','total'))    $payload['total']    = $total;

        if ($payload) {
            $q->update($payload);
        }
    }

    private function storeAttachments(Quotation $q, array $files = []): void
    {
        if (empty($files) || !method_exists($q, 'attachments')) {
            return;
        }

        foreach ($files as $file) {
            if (!$file) continue;
            $path = $file->store('quotation_attachments', 'public');

            $q->attachments()->create([
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
            ]);
        }
    }

    /**
     * Fallback: สร้าง Invoice จาก Quotation ตรง ๆ (ไม่พึ่ง service, ไม่ติด fillable)
     */
    private function inlineCreateInvoice(Quotation $q): ?Invoice
    {
        $hasLinkColumn = Schema::hasColumn('invoices', 'quotation_id');

        if ($hasLinkColumn && method_exists($q, 'invoice') && $q->invoice) {
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
            if (Schema::hasColumn('invoices', 'quotation_id')) {
                $inv->quotation_id = $q->id;
            }
            if (Schema::hasColumn('invoices', 'quotation_number')) {
                $inv->quotation_number = $q->number ?? ('QT'.$q->id);
            }
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

    private function inlineCreatePurchaseOrder(Quotation $q): ?Invoice
    {
        return DB::transaction(function () use ($q) {
            $q->loadMissing('items');

            $period = ($q->issue_date ?: now())->format('Y-m');
            $prefix = 'PO'.$period.'-';
            $lastNo = PurchaseOrder::where('number','like',$prefix.'%')->orderByDesc('id')->value('number');
            $seq = 0;
            if ($lastNo && preg_match('/-(\d{4})$/', $lastNo, $m)) $seq = (int)$m[1];
            $seq++;
            $poNo = $prefix.str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

            $po = new PurchaseOrder();
            $po->number        = $poNo;
            $po->customer_name = (string) $q->customer_name;
            $po->issue_date    = ($q->issue_date ?: now())->toDateString();
            $po->due_date      = \Illuminate\Support\Carbon::parse($po->issue_date)->addDays(14)->toDateString();
            $po->tax_rate      = (float) ($q->tax_rate ?? 0);
            $po->subtotal      = 0;
            $po->tax           = 0;
            $po->total         = 0;
            $po->status        = 'draft';
            $po->save();

            $subtotal = 0.0;
            foreach ($q->items as $it) {
                $qty   = (float)($it->qty ?? $it->quantity ?? 0);
                $price = (float)($it->price ?? $it->unit_price ?? 0);
                $disc  = (float)($it->discount ?? 0);
                $line  = round(($qty * $price) - $disc, 2);

                $item = new InvoiceItem();
                $item->invoice_id = $po->id;
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

            $tax   = round($subtotal * ((float)$po->tax_rate/100), 2);
            $total = round($subtotal + $tax, 2);

            $po->subtotal = $subtotal;
            $po->tax      = $tax;
            $po->total    = $total;
            $po->save();

            return $po->fresh('items');
        });
    }

    private function logAction(Quotation $quotation, string $action, ?string $description = null): void
    {
        if (!Schema::hasTable('quotation_logs')) {
            return;
        }

        $payload = [
            'quotation_id' => $quotation->id,
            'action'       => $action,
            'description'  => $description,
        ];

        try {
            $user = auth()->user();
            if ($user) {
                $payload['user_id'] = $user->id;
                $payload['user_name'] = $user->name ?? $user->email ?? ('user#'.$user->id);
            }
        } catch (\Throwable $e) {
            // auth guard not available; skip user info
        }

        QuotationLog::create($payload);
    }
}
