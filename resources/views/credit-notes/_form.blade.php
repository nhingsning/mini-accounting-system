@php
  $isEdit = isset($note);
  $action = $isEdit ? route('credit-notes.update', $note->number ?? $note->id) : route('credit-notes.store');
  $invoice = $invoice ?? ($note->invoice ?? null);
  $items = old('items');
  if ($items === null) {
    if ($isEdit) {
      $items = $note->items?->map(function($it){
        return [
          'description' => $it->description,
          'qty' => $it->qty,
          'unit_price' => $it->unit_price,
          'line_total' => $it->line_total,
          'unit' => $it->unit,
        ];
      })->toArray();
    } elseif ($invoice) {
      $items = $invoice->items?->map(function($it){
        $qty = $it->qty ?? $it->quantity ?? 1;
        $price = $it->unit_price ?? $it->price ?? 0;
        return [
          'description' => $it->description,
          'qty' => $qty,
          'unit_price' => $price,
          'line_total' => $it->line_total ?? $qty * $price,
          'unit' => $it->unit ?? null,
        ];
      })->toArray();
    }
  }
  $items = $items ?? [[]];

  $status = old('status', $isEdit ? $note->status : 'draft');
  $type = old('type', $isEdit ? $note->type : 'credit');
  $statusOptions = $statusOptions ?? [
    'draft' => 'Draft',
    'issued' => 'Issued',
    'cancelled' => 'Cancelled / Void',
  ];
@endphp

<style>
:root{--brand:#31689E;--ink:#0f172a;--muted:#6b7280;--line:#e5e7eb;--card:#fff;--bg:#f8fafc}
.cn-wrap{max-width:1200px;margin:0 auto;padding:18px 18px 28px}
.cn-top{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.cn-title{font-size:22px;font-weight:800;color:var(--ink);letter-spacing:-0.01em;margin-bottom:4px}
.cn-sub{color:var(--muted);font-size:13px;margin-bottom:6px}
.cn-badge{display:inline-flex;align-items:center;gap:8px;background:#e9f2fb;border:1px solid #c5dbf1;color:var(--brand);padding:6px 12px;border-radius:999px;font-weight:700;font-size:12px}
.cn-number{font-size:18px;font-weight:800;color:var(--brand);letter-spacing:0.06em}
.cn-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.cn-btn{display:inline-flex;align-items:center;gap:6px;border-radius:10px;border:1px solid var(--line);padding:10px 14px;font-weight:700;text-decoration:none;background:#fff;color:var(--ink);box-shadow:0 8px 20px -15px rgba(15,23,42,0.2)}
.cn-btn.primary{background:var(--brand);color:#fff;border-color:var(--brand);box-shadow:0 10px 25px -18px rgba(49,104,158,0.8)}
.cn-grid{display:grid;grid-template-columns:1fr 320px;gap:18px}
@media(max-width:1024px){.cn-grid{grid-template-columns:1fr}}
.cn-card{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:18px 18px 20px;box-shadow:0 16px 50px -35px rgba(15,23,42,0.35)}
.section-title{font-weight:800;color:var(--ink);margin-bottom:10px;font-size:15px;display:flex;gap:8px;align-items:center}
.cn-label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;font-weight:800}
.cn-input,.cn-textarea,.cn-select{width:100%;border:1px solid var(--line);border-radius:12px;padding:10px 12px;font-size:14px;background:#fff}
.cn-textarea{min-height:90px}
.cn-two{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media(max-width:768px){.cn-two{grid-template-columns:1fr}}
.cn-helper{font-size:12px;color:var(--muted);margin-top:4px}
.cn-table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid var(--line);border-radius:12px;overflow:hidden;margin-top:10px}
.cn-table thead th{background:#edf4fb;border:0;padding:10px 12px;font-weight:800;color:#1f2937}
.cn-table tbody td{background:#fff;border-bottom:1px solid var(--line);padding:8px 10px;vertical-align:middle}
.cn-table input{width:100%;border:1px solid var(--line);border-radius:8px;padding:8px 10px;font-size:13px}
.cn-sticky{position:sticky;top:12px;display:flex;flex-direction:column;gap:12px}
.cn-totals .row{display:flex;justify-content:space-between;font-size:14px;margin:6px 0}
.cn-totals strong{color:var(--ink)}
.cn-alert{border:1px solid #fecdd3;background:#fff1f2;border-radius:12px;padding:10px 12px;color:#991b1b;margin-bottom:12px}
</style>

<form method="POST" action="{{ $action }}" class="cn-form" autocomplete="off">
  @csrf
  @if($isEdit)
    @method('PUT')
  @endif
  <div class="cn-wrap">
    <div class="cn-top">
      <div>
        <div class="cn-sub">{{ $type === 'debit' ? 'Debit Note' : 'Credit Note' }}</div>
        <div class="cn-title">{{ $isEdit ? 'แก้ไขเอกสาร' : 'สร้างเอกสารใหม่' }}</div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
          <span class="cn-number">{{ old('number', $isEdit ? ($note->number ?? 'Draft') : 'Draft') }}</span>
          <span class="cn-badge">{{ $statusOptions[$status] ?? ucfirst($status) }}</span>
          @if($invoice)
            <span class="cn-badge" style="background:#fef3c7;border-color:#fcd34d;color:#92400e;">INV {{ $invoice->number ?? $invoice->id }}</span>
          @endif
        </div>
      </div>
      <div class="cn-actions">
        <a href="{{ route('credit-notes.index') }}" class="cn-btn">ย้อนกลับ</a>
        <button type="submit" class="cn-btn primary">{{ $isEdit ? 'บันทึกการแก้ไข' : 'บันทึกเอกสาร' }}</button>
      </div>
    </div>

    @if ($errors->any())
      <div class="cn-alert">
        <strong>กรอกไม่ครบหรือไม่ถูกต้อง:</strong>
        <ul style="margin:6px 0 0 18px">
          @foreach ($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="cn-grid">
      <div class="cn-card">
        <div class="section-title">ข้อมูลเอกสาร</div>
        <div class="cn-two" style="margin-bottom:12px">
          <div>
            <label class="cn-label">เลขเอกสาร</label>
            <input class="cn-input" name="number" value="{{ old('number', $isEdit ? $note->number : '') }}" placeholder="ปล่อยว่างให้ออกเลขอัตโนมัติ">
            <div class="cn-helper">รองรับการแก้เลขเอง (ไม่ซ้ำ)</div>
          </div>
          <div>
            <label class="cn-label">ประเภท</label>
            <select name="type" class="cn-select">
              <option value="credit" {{ $type==='credit' ? 'selected' : '' }}>Credit Note (ลดหนี้/คืนของ)</option>
              <option value="debit" {{ $type==='debit' ? 'selected' : '' }}>Debit Note (เพิ่มหนี้/ปรับยอด)</option>
            </select>
          </div>
        </div>
        <div class="cn-two" style="margin-bottom:12px">
          <div>
            <label class="cn-label">สถานะ</label>
            <select name="status" class="cn-select">
              @foreach($statusOptions as $value => $label)
                <option value="{{ $value }}" {{ $status===$value ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="cn-label">วันที่</label>
            <input type="date" class="cn-input" name="issue_date" value="{{ old('issue_date', $isEdit ? optional($note->issue_date)->toDateString() : now()->toDateString()) }}">
          </div>
        </div>
        <div class="cn-two" style="margin-bottom:12px">
          <div>
            <label class="cn-label">อ้างอิง Invoice</label>
            <input class="cn-input" name="invoice_number" value="{{ old('invoice_number', $isEdit ? $note->invoice_number : ($invoice->number ?? '')) }}" placeholder="INV...">
            <input type="hidden" name="invoice_id" value="{{ old('invoice_id', $isEdit ? $note->invoice_id : ($invoice->id ?? '')) }}">
            <div class="cn-helper">เชื่อมโยงกับใบแจ้งหนี้ที่ต้องการปรับยอด</div>
          </div>
          <div>
            <label class="cn-label">เหตุผล / หมายเหตุ</label>
            <input class="cn-input" name="reason" value="{{ old('reason', $isEdit ? $note->reason : '') }}" placeholder="คืนสินค้า, ส่วนลด, ปรับยอด ฯลฯ">
          </div>
        </div>

        <div class="section-title">ข้อมูลลูกค้า</div>
        <div class="cn-two" style="margin-bottom:12px">
          <div>
            <label class="cn-label">ชื่อลูกค้า</label>
            <input class="cn-input" name="customer_name" value="{{ old('customer_name', $isEdit ? $note->customer_name : ($invoice->customer_name ?? '')) }}">
          </div>
          <div>
            <label class="cn-label">Tax ID</label>
            <input class="cn-input" name="customer_tax_id" value="{{ old('customer_tax_id', $isEdit ? $note->customer_tax_id : ($invoice->customer_tax_id ?? '')) }}">
          </div>
          <div>
            <label class="cn-label">Branch Type</label>
            @php $bt = old('customer_branch_type', $isEdit ? $note->customer_branch_type : ($invoice->customer_branch_type ?? '')); @endphp
            <select name="customer_branch_type" class="cn-select">
              <option value="" {{ $bt===''?'selected':'' }}>—</option>
              <option value="head" {{ $bt==='head'?'selected':'' }}>Head Office</option>
              <option value="branch" {{ $bt==='branch'?'selected':'' }}>Branch</option>
            </select>
          </div>
          <div>
            <label class="cn-label">Branch Code</label>
            <input class="cn-input" name="customer_branch_code" value="{{ old('customer_branch_code', $isEdit ? $note->customer_branch_code : ($invoice->customer_branch_code ?? '')) }}">
          </div>
          <div class="cn-two" style="grid-column:1/-1">
            <div style="grid-column:1/-1">
              <label class="cn-label">ที่อยู่ลูกค้า</label>
              <textarea class="cn-textarea" name="customer_address" rows="3" placeholder="ที่อยู่ อีเมล หรือข้อมูลติดต่อ">{{ old('customer_address', $isEdit ? $note->customer_address : ($invoice->customer_address ?? '')) }}</textarea>
            </div>
          </div>
        </div>

        <div class="section-title">รายการสินค้า/บริการ</div>
        <div class="cn-helper">ปรับปริมาณ/ราคาเป็นค่าลบสำหรับใบลดหนี้ หรือบวกสำหรับใบเพิ่มหนี้</div>
        <table class="cn-table">
          <thead>
            <tr>
              <th style="width:45%">รายการ</th>
              <th style="width:12%">Qty</th>
              <th style="width:18%">ราคา/หน่วย</th>
              <th style="width:15%">หน่วย</th>
              <th style="width:15%">รวม</th>
            </tr>
          </thead>
          <tbody id="cn-items">
            @foreach($items as $idx => $it)
              <tr>
                <td><input name="items[{{ $idx }}][description]" value="{{ $it['description'] ?? '' }}" placeholder="รายละเอียด"></td>
                <td><input name="items[{{ $idx }}][qty]" type="number" step="0.01" value="{{ $it['qty'] ?? 1 }}"></td>
                <td><input name="items[{{ $idx }}][unit_price]" type="number" step="0.01" value="{{ $it['unit_price'] ?? 0 }}"></td>
                <td><input name="items[{{ $idx }}][unit]" value="{{ $it['unit'] ?? '' }}"></td>
                <td><input name="items[{{ $idx }}][line_total]" type="number" step="0.01" value="{{ $it['line_total'] ?? 0 }}"></td>
              </tr>
            @endforeach
          </tbody>
        </table>
        <button type="button" class="cn-btn" style="margin-top:10px" onclick="addCNRow()">+ เพิ่มรายการ</button>
      </div>

      <div class="cn-card cn-sticky">
        <div>
          <div class="section-title">สรุปยอด</div>
          <div class="cn-helper">ยอดรวมจะใช้ตามรายการด้านซ้าย ถ้าปรับเองให้ใส่ตัวเลข</div>
          <div class="cn-totals">
            <div class="row"><span>Subtotal</span><input class="cn-input" type="number" step="0.01" name="subtotal" value="{{ old('subtotal', $isEdit ? $note->subtotal : ($invoice->subtotal ?? 0)) }}"></div>
            <div class="row"><span>Tax</span><input class="cn-input" type="number" step="0.01" name="tax" value="{{ old('tax', $isEdit ? $note->tax : 0) }}"></div>
            <div class="row"><span><strong>Total</strong></span><input class="cn-input" type="number" step="0.01" name="total" value="{{ old('total', $isEdit ? $note->total : ($invoice->total ?? 0)) }}"></div>
            <div class="row"><span>Currency</span><input class="cn-input" name="currency" value="{{ old('currency', $isEdit ? ($note->currency ?? 'THB') : ($invoice->currency ?? 'THB')) }}"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
<script>
  function addCNRow(){
    const tbody = document.getElementById('cn-items');
    const idx = tbody.children.length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input name="items[${idx}][description]" placeholder="รายละเอียด"></td>
      <td><input name="items[${idx}][qty]" type="number" step="0.01" value="1"></td>
      <td><input name="items[${idx}][unit_price]" type="number" step="0.01" value="0"></td>
      <td><input name="items[${idx}][unit]" value=""></td>
      <td><input name="items[${idx}][line_total]" type="number" step="0.01" value="0"></td>
    `;
    tbody.appendChild(tr);
  }
</script>
