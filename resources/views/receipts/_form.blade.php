@php
  $isEdit = isset($receipt);
  $action = $isEdit ? route('receipts.update', $receipt->number ?? $receipt->id) : route('receipts.store');
  $invoice = $invoice ?? ($receipt->invoice ?? null);

  $items = old('items');
  if ($items === null && $invoice) {
    $items = $invoice->items?->map(function ($it) {
      return [
        'description' => $it->description,
        'qty' => $it->qty ?? $it->quantity ?? 1,
        'unit_price' => $it->unit_price ?? $it->price ?? 0,
        'line_total' => $it->line_total ?? ($it->qty ?? 1) * ($it->unit_price ?? $it->price ?? 0),
        'unit' => $it->unit ?? null,
      ];
    })->toArray();
  }

  $items = $items ?? [];
  $subtotal = $invoice->subtotal ?? collect($items)->sum(function($it){
    return (float)($it['line_total'] ?? (($it['qty'] ?? 1) * ($it['unit_price'] ?? 0)));
  });
  $tax = $invoice->tax ?? 0;
  $total = old('total', $isEdit ? ($receipt->total ?? 0) : ($invoice->total ?? $subtotal));
  $currency = old('currency', $isEdit ? ($receipt->currency ?? 'THB') : ($invoice->currency ?? 'THB'));
  $status = old('status', $isEdit ? $receipt->status : 'draft');
  $statusOptions = [
    'draft'     => 'Draft',
    'issued'    => 'Issued / Completed',
    'cancelled' => 'Cancelled / Void',
  ];
  $statusLabel = $statusOptions[$status] ?? ucfirst($status);
@endphp

<style>
:root{--brand:#31689E;--ink:#0f172a;--muted:#6b7280;--line:#e5e7eb;--bg:#f8fafc;--card:#ffffff}
.fa-wrap{max-width:1200px;margin:0 auto;padding:18px 18px 28px}
.fa-topbar{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:16px}
.fa-title{font-size:22px;font-weight:800;color:var(--ink);letter-spacing:-0.01em;margin-bottom:6px}
.fa-subtitle{color:var(--muted);font-size:13px;margin-bottom:4px}
.fa-number{font-size:18px;font-weight:800;color:var(--brand);letter-spacing:0.04em}
.fa-badge{display:inline-flex;align-items:center;gap:8px;background:#e9f2fb;color:var(--brand);border:1px solid #c5dbf1;padding:4px 12px;border-radius:999px;font-weight:700;font-size:12px}
.fa-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.fa-btn{display:inline-flex;align-items:center;gap:6px;border-radius:10px;border:1px solid var(--line);padding:10px 14px;text-decoration:none;font-weight:700;box-shadow:0 8px 20px -15px rgba(15,23,42,0.2);transition:all .15s ease;background:#fff;color:var(--ink)}
.fa-btn.save{background:var(--brand);color:#fff;border-color:var(--brand);box-shadow:0 10px 25px -18px rgba(49,104,158,0.9)}
.fa-btn.ghost{background:#f1f5f9;color:var(--ink);border-color:#d9e3ef}
.fa-btn:hover{transform:translateY(-1px);box-shadow:0 12px 28px -20px rgba(15,23,42,0.35)}
.fa-card{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:0 16px 50px -35px rgba(15,23,42,0.35)}
.fa-grid{display:grid;grid-template-columns:1fr 320px;gap:18px}
@media (max-width: 1024px){.fa-grid{grid-template-columns:1fr}}
.fa-section{padding:18px 18px 20px}
.section-title{font-weight:700;color:var(--ink);font-size:15px;margin-bottom:10px;display:flex;align-items:center;gap:8px}
.fa-label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;font-weight:700}
.fa-input,.fa-textarea,.fa-select{width:100%;background:#fff;border:1px solid var(--line);border-radius:12px;padding:10px 12px;font-size:14px;transition:border-color .12s,box-shadow .12s}
.fa-input:focus,.fa-textarea:focus,.fa-select:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(49,104,158,.08)}
.fa-textarea{min-height:92px}
.fa-two{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:6px}
.fa-two .span-2{grid-column:1/-1}
@media (max-width: 768px){.fa-two{grid-template-columns:1fr}}
.fa-table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid var(--line);border-radius:12px;overflow:hidden}
.fa-table thead th{background:#edf4fb;color:#1f2937;border:0;padding:10px 12px;font-weight:800}
.fa-table tbody td{background:#fff;border-bottom:1px solid var(--line);padding:10px 12px;vertical-align:middle}
.fa-table .qty,.fa-table .price,.fa-table .line{text-align:right;width:140px}
.fa-sticky{position:sticky;top:12px;display:flex;flex-direction:column;gap:12px}
.fa-totals .row{display:flex;justify-content:space-between;margin:7px 0;font-size:14px}
.fa-totals .row strong{font-weight:800;color:var(--ink)}
.helper{color:var(--muted);font-size:12px;margin-top:4px}
.alert{border-radius:12px;padding:10px 12px;border:1px solid;margin-bottom:12px}
.alert-danger{background:#fff1f2;border-color:#fecdd3;color:#991b1b}
</style>

<form id="receiptForm" method="POST" action="{{ $action }}" autocomplete="off">
  @csrf
  @if($isEdit)
    @method('PUT')
  @endif

  <div class="fa-wrap">
    <div class="fa-topbar">
      <div>
        <div class="fa-subtitle">Receipt</div>
        <div class="fa-title">{{ $isEdit ? 'แก้ไขใบเสร็จ' : 'สร้างใบเสร็จ' }}</div>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
          <span class="fa-number">{{ old('number', $isEdit ? ($receipt->number ?? 'Draft') : 'Draft') }}</span>
          <span class="fa-badge">{{ $statusLabel }}</span>
          @if($invoice)
            <span class="fa-badge" style="background:#fef3c7;border-color:#fcd34d;color:#92400e;">INV {{ $invoice->number ?? ('#'.$invoice->id) }}</span>
          @endif
        </div>
      </div>
      <div class="fa-actions">
        <a href="{{ route('receipts.index') }}" class="fa-btn ghost">ย้อนกลับ</a>
        <button type="submit" class="fa-btn save">{{ $isEdit ? 'บันทึกการแก้ไข' : 'บันทึกใบเสร็จ' }}</button>
      </div>
    </div>

    @if ($errors->any())
      <div class="alert alert-danger" role="alert">
        <strong>กรอกไม่ครบหรือไม่ถูกต้อง:</strong>
        <ul style="margin:6px 0 0 18px">
          @foreach ($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="fa-grid">
      <div class="fa-card fa-section">
        <div class="section-title">ข้อมูลใบเสร็จ &amp; ลูกค้า</div>

        <div class="fa-two">
          <div>
            <label class="fa-label">Receipt No.</label>
            <input name="number" class="fa-input" value="{{ old('number', $isEdit ? $receipt->number : '') }}" placeholder="ปล่อยว่างให้ออกเลขอัตโนมัติ">
            <div class="helper">กรณีปรับเลขเองให้ไม่ซ้ำ</div>
          </div>
          <div>
            <label class="fa-label">Invoice No.</label>
            <input name="invoice_number" class="fa-input" value="{{ old('invoice_number', $isEdit ? $receipt->invoice_number : ($invoice->number ?? '')) }}" placeholder="INV...">
            <input type="hidden" name="invoice_id" value="{{ old('invoice_id', $isEdit ? $receipt->invoice_id : ($invoice->id ?? '')) }}">
            <div class="helper">ดึงข้อมูลจากใบแจ้งหนี้ที่เกี่ยวข้อง</div>
          </div>
        </div>

        <div class="fa-two">
          <div>
            <label class="fa-label">สถานะ</label>
            <select name="status" class="fa-select">
              @foreach($statusOptions as $value => $label)
                <option value="{{ $value }}" {{ $status===$value ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
            <div class="helper">อัปเดตสถานะใบเสร็จได้ทันที</div>
          </div>
          <div>
            <label class="fa-label">Issue Date</label>
            <input type="date" name="issue_date" class="fa-input" value="{{ old('issue_date', ($isEdit ? optional($receipt->issue_date)->toDateString() : now()->toDateString())) }}">
          </div>
        </div>

        <div class="fa-two">
          <div>
            <label class="fa-label">Customer Name</label>
            <input name="customer_name" class="fa-input" value="{{ old('customer_name', $isEdit ? $receipt->customer_name : ($invoice->customer_name ?? '')) }}" required>
          </div>
          <div>
            <label class="fa-label">Tax ID</label>
            <input name="customer_tax_id" class="fa-input" value="{{ old('customer_tax_id', $isEdit ? $receipt->customer_tax_id : ($invoice->customer_tax_id ?? '')) }}" placeholder="เลขประจำตัวผู้เสียภาษี">
          </div>
          <div class="span-2">
            <label class="fa-label">Customer Address</label>
            <textarea name="customer_address" rows="3" class="fa-textarea" placeholder="ที่อยู่ลูกค้า">{{ old('customer_address', $isEdit ? $receipt->customer_address : ($invoice->customer_address ?? '')) }}</textarea>
          </div>
          <div>
            <label class="fa-label">Branch Type</label>
            @php $bt = old('customer_branch_type', $isEdit ? $receipt->customer_branch_type : ($invoice->customer_branch_type ?? '')); @endphp
            <select name="customer_branch_type" class="fa-select">
              <option value="" {{ $bt===''?'selected':'' }}>—</option>
              <option value="head" {{ $bt==='head'?'selected':'' }}>Head Office</option>
              <option value="branch" {{ $bt==='branch'?'selected':'' }}>Branch</option>
            </select>
          </div>
          <div>
            <label class="fa-label">Branch Code</label>
            <input name="customer_branch_code" class="fa-input" value="{{ old('customer_branch_code', $isEdit ? $receipt->customer_branch_code : ($invoice->customer_branch_code ?? '')) }}" placeholder="เช่น 00000">
          </div>
        </div>

        <div class="fa-two">
          <div>
            <label class="fa-label">Currency</label>
            <input name="currency" class="fa-input" value="{{ $currency }}">
          </div>
          <div>
            <label class="fa-label">Total</label>
            <input type="number" step="0.01" name="total" class="fa-input" value="{{ $total }}" required>
            <div class="helper">จำนวนเงินรวมตามใบแจ้งหนี้</div>
          </div>
        </div>

        <div style="margin-top:18px">
          <div class="section-title" style="margin-bottom:6px">รายการสินค้า/บริการ</div>
          <div class="helper">แสดงรายการจากใบแจ้งหนี้เพื่อความถูกต้อง</div>
          @if(count($items))
            <div class="fa-table" style="margin-top:10px">
              <table class="fa-table">
                <thead>
                  <tr>
                    <th>Description</th>
                    <th class="qty">Qty</th>
                    <th class="price">Unit Price</th>
                    <th class="line">Line Total</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($items as $it)
                    <tr>
                      <td>{{ $it['description'] ?? '—' }} @if(!empty($it['unit']))<div class="helper" style="margin:2px 0 0">หน่วย: {{ $it['unit'] }}</div>@endif</td>
                      <td class="qty">{{ number_format($it['qty'] ?? 1, 2) }}</td>
                      <td class="price">{{ number_format($it['unit_price'] ?? 0, 2) }}</td>
                      <td class="line">{{ number_format($it['line_total'] ?? 0, 2) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <div class="helper" style="margin-top:10px">ยังไม่มีรายการสินค้าในใบแจ้งหนี้นี้</div>
          @endif
        </div>
      </div>

      <div class="fa-sticky">
        <div class="fa-card fa-section">
          <div class="section-title">สรุปยอด</div>
          <div class="fa-totals">
            <div class="row"><span>Subtotal</span><strong>{{ number_format($subtotal, 2) }} {{ $currency }}</strong></div>
            <div class="row"><span>VAT</span><strong>{{ number_format($tax, 2) }} {{ $currency }}</strong></div>
            <hr style="margin:10px 0;">
            <div class="row" style="font-size:16px"><span>Total</span><strong>{{ number_format($total, 2) }} {{ $currency }}</strong></div>
            <div class="row"><span>Status</span><strong>{{ $statusLabel }}</strong></div>
          </div>
          @if($invoice)
            <div class="helper" style="margin-top:8px">ดึงยอดจากใบแจ้งหนี้ {{ $invoice->number ?? ('#'.$invoice->id) }}</div>
          @endif
        </div>

        <div class="fa-card fa-section">
          <div class="section-title">ข้อมูลอ้างอิง</div>
          <div class="fa-totals">
            <div class="row"><span>Invoice</span><strong>{{ $invoice->number ?? '—' }}</strong></div>
            <div class="row"><span>ลูกค้า</span><strong>{{ old('customer_name', $isEdit ? $receipt->customer_name : ($invoice->customer_name ?? '—')) }}</strong></div>
            <div class="row"><span>สถานะ</span><strong>{{ $statusLabel }}</strong></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
