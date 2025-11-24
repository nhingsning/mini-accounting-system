@php
  $isEdit = isset($note);
  $action = $isEdit ? route('credit-notes.update', $note->number ?? $note->id) : route('credit-notes.store');
  $invoice = $invoice ?? ($note->invoice ?? null);
  $invoiceOptions = $invoiceOptions ?? collect();
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
  $docTitle = $type === 'debit' ? 'Debit Note' : 'Credit Note';
  $invoicePayload = $invoiceOptions->map(function($inv){
    return [
      'id' => $inv->id,
      'number' => $inv->number,
      'issue_date' => optional($inv->issue_date)->toDateString(),
      'customer_name' => $inv->customer_name,
      'customer_address' => $inv->customer_address,
      'customer_tax_id' => $inv->customer_tax_id,
      'customer_branch_type' => $inv->customer_branch_type,
      'customer_branch_code' => $inv->customer_branch_code,
      'subtotal' => $inv->subtotal,
      'tax' => $inv->tax,
      'total' => $inv->total,
      'currency' => $inv->currency ?? 'THB',
      'items' => $inv->items?->map(function($it){
        return [
          'description' => $it->description,
          'qty' => $it->qty ?? $it->quantity ?? 1,
          'unit_price' => $it->unit_price ?? $it->price ?? 0,
          'unit' => $it->unit,
        ];
      })->values()->all(),
    ];
  })->values();
@endphp

<style>
:root{--brand:#31689E;--ink:#0f172a;--muted:#6b7280;--line:#e5e7eb;--bg:#f8fafc;--card:#ffffff}
body{background:var(--bg);color:var(--ink)}
.fa-wrap{max-width:1220px;margin:0 auto;padding:18px 18px 24px}
.fa-topbar{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;gap:16px;flex-wrap:wrap}
.fa-title{font-size:22px;font-weight:800;color:var(--ink);letter-spacing:-0.01em;margin-bottom:6px}
.fa-subtitle{color:var(--muted);font-size:13px;margin-bottom:6px}
.fa-badge{display:inline-flex;align-items:center;gap:8px;background:#e9f2fb;color:var(--brand);border:1px solid #c5dbf1;padding:4px 12px;border-radius:999px;font-weight:700;font-size:12px}
.fa-number{font-size:18px;font-weight:800;color:var(--brand);letter-spacing:0.04em}
.fa-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.fa-btn{display:inline-flex;align-items:center;gap:6px;border-radius:10px;border:1px solid var(--line);padding:10px 14px;text-decoration:none;font-weight:700;box-shadow:0 8px 20px -15px rgba(15,23,42,0.2);transition:all .15s ease;background:#fff;color:var(--ink)}
.fa-btn.save{background:var(--brand);color:#fff;border-color:var(--brand);box-shadow:0 10px 25px -18px rgba(49,104,158,0.9)}
.fa-btn:hover{transform:translateY(-1px);box-shadow:0 12px 28px -20px rgba(15,23,42,0.35)}
.fa-card{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:0 16px 50px -35px rgba(15,23,42,0.35)}
.fa-grid{display:grid;grid-template-columns:1fr 320px;gap:18px}
@media (max-width: 1024px){.fa-grid{grid-template-columns:1fr}}
.fa-section{padding:18px 18px 20px}
.fa-section .section-title{font-weight:700;color:var(--ink);font-size:15px;margin-bottom:10px;display:flex;align-items:center;gap:8px}
.fa-label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em;font-weight:700}
.fa-input,.fa-textarea,.fa-select{width:100%;background:#fff;border:1px solid var(--line);border-radius:12px;padding:10px 12px;font-size:14px;transition:border-color .12s,box-shadow .12s}
.fa-input:focus,.fa-textarea:focus,.fa-select:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(49,104,158,.08)}
.fa-textarea{min-height:92px}
.fa-two{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:6px}
.fa-two .span-2{grid-column:1/-1}
@media (max-width: 768px){.fa-two{grid-template-columns:1fr}}
.fa-table{width:100%;border-collapse:separate;border-spacing:0 0;border:1px solid var(--line);border-radius:12px;overflow:hidden}
.fa-table thead th{background:#edf4fb;color:#1f2937;border:0;padding:10px 12px;font-weight:800}
.fa-table tbody td{background:#fff;border-bottom:1px solid var(--line);padding:10px 12px;vertical-align:middle}
.fa-table .qty,.fa-table .price,.fa-table .line{text-align:right;width:140px}
.fa-table input{width:100%;border:1px solid var(--line);border-radius:8px;padding:8px 10px;font-size:13px}
.fa-sticky{position:sticky;top:12px;display:flex;flex-direction:column;gap:12px}
.fa-totals .row{display:flex;justify-content:space-between;margin:7px 0;font-size:14px;gap:8px}
.fa-totals .row strong{font-weight:800;color:var(--ink)}
.alert{border-radius:12px;padding:10px 12px;border:1px solid;margin-bottom:12px}
.alert-danger{background:#fff1f2;border-color:#fecdd3;color:#991b1b}
.helper{color:var(--muted);font-size:12px;margin-top:4px}
</style>

<form method="POST" action="{{ $action }}" class="cn-form" autocomplete="off">
  @csrf
  @if($isEdit)
    @method('PUT')
  @endif
  <div class="fa-wrap">
    <div class="fa-topbar">
      <div>
        <div class="fa-subtitle">{{ $docTitle }}</div>
        <div class="fa-title">{{ $isEdit ? 'แก้ไขเอกสาร' : 'สร้างเอกสารใหม่' }}</div>
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
          <span class="fa-number">{{ old('number', $isEdit ? ($note->number ?? 'Draft') : 'Draft') }}</span>
          <span class="fa-badge">{{ $statusOptions[$status] ?? ucfirst($status) }}</span>
          @if($invoice)
            <span class="fa-badge" style="background:#fef3c7;border-color:#fcd34d;color:#92400e;">INV {{ $invoice->number ?? $invoice->id }}</span>
          @endif
        </div>
      </div>
      <div class="fa-actions">
        <a href="{{ route('credit-notes.index') }}" class="fa-btn">ย้อนกลับ</a>
        <button type="submit" class="fa-btn save">{{ $isEdit ? 'บันทึกการแก้ไข' : 'บันทึกเอกสาร' }}</button>
      </div>
    </div>

    @if ($errors->any())
      <div class="alert alert-danger">
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
        <div class="section-title">ข้อมูลเอกสาร</div>
        <div class="fa-two">
          <div>
            <label class="fa-label">เลขเอกสาร</label>
            <input class="fa-input" name="number" value="{{ old('number', $isEdit ? $note->number : '') }}" placeholder="ปล่อยว่างให้ออกเลขอัตโนมัติ">
            <div class="helper">รองรับการแก้เลขเอง (ไม่ซ้ำ)</div>
          </div>
          <div>
            <label class="fa-label">สถานะ</label>
            <select name="status" class="fa-select">
              @foreach($statusOptions as $value => $label)
                <option value="{{ $value }}" {{ $status===$value ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="fa-two" style="margin-top:10px">
          <div>
            <label class="fa-label">ประเภท</label>
            <select name="type" class="fa-select">
              <option value="credit" {{ $type==='credit' ? 'selected' : '' }}>Credit Note (ลดหนี้/คืนของ)</option>
              <option value="debit" {{ $type==='debit' ? 'selected' : '' }}>Debit Note (เพิ่มหนี้/ปรับยอด)</option>
            </select>
          </div>
          <div>
            <label class="fa-label">วันที่</label>
            <input type="date" class="fa-input" name="issue_date" value="{{ old('issue_date', $isEdit ? optional($note->issue_date)->toDateString() : now()->toDateString()) }}">
          </div>
        </div>
        <div class="fa-two" style="margin-top:10px">
          <div>
            <label class="fa-label">อ้างอิง Invoice</label>
            <select class="fa-select" name="invoice_id" id="invoice-select">
              <option value="">เลือก Invoice</option>
              @foreach($invoiceOptions as $inv)
                <option value="{{ $inv->id }}" {{ (string)old('invoice_id', $isEdit ? $note->invoice_id : ($invoice->id ?? '')) === (string)$inv->id ? 'selected' : '' }}>
                  {{ $inv->number }} — {{ $inv->customer_name }}
                </option>
              @endforeach
            </select>
            <input class="fa-input" style="margin-top:8px" id="invoice-number" name="invoice_number" value="{{ old('invoice_number', $isEdit ? $note->invoice_number : ($invoice->number ?? '')) }}" placeholder="INV...">
            <div class="helper">เลือกหรือกรอกใบแจ้งหนี้ที่จะอ้างอิง</div>
          </div>
          <div>
            <label class="fa-label">เหตุผล / หมายเหตุ</label>
            <input class="fa-input" name="reason" value="{{ old('reason', $isEdit ? $note->reason : '') }}" placeholder="คืนสินค้า, ส่วนลด, ปรับยอด ฯลฯ">
          </div>
        </div>

        <div class="section-title" style="margin-top:18px">ข้อมูลลูกค้า</div>
        <div class="fa-two">
          <div>
            <label class="fa-label">ชื่อลูกค้า</label>
            <input class="fa-input" name="customer_name" value="{{ old('customer_name', $isEdit ? $note->customer_name : ($invoice->customer_name ?? '')) }}">
          </div>
          <div>
            <label class="fa-label">Tax ID</label>
            <input class="fa-input" name="customer_tax_id" value="{{ old('customer_tax_id', $isEdit ? $note->customer_tax_id : ($invoice->customer_tax_id ?? '')) }}">
          </div>
        </div>
        <div class="fa-two" style="margin-top:10px">
          @php $bt = old('customer_branch_type', $isEdit ? $note->customer_branch_type : ($invoice->customer_branch_type ?? '')); @endphp
          <div>
            <label class="fa-label">Branch Type</label>
            <select name="customer_branch_type" class="fa-select">
              <option value="" {{ $bt===''?'selected':'' }}>—</option>
              <option value="head" {{ $bt==='head'?'selected':'' }}>Head Office</option>
              <option value="branch" {{ $bt==='branch'?'selected':'' }}>Branch</option>
            </select>
          </div>
          <div>
            <label class="fa-label">Branch Code</label>
            <input class="fa-input" name="customer_branch_code" value="{{ old('customer_branch_code', $isEdit ? $note->customer_branch_code : ($invoice->customer_branch_code ?? '')) }}">
          </div>
          <div class="span-2">
            <label class="fa-label">ที่อยู่ลูกค้า</label>
            <textarea class="fa-textarea" name="customer_address" rows="3" placeholder="ที่อยู่ อีเมล หรือข้อมูลติดต่อ">{{ old('customer_address', $isEdit ? $note->customer_address : ($invoice->customer_address ?? '')) }}</textarea>
          </div>
        </div>

        <div class="section-title" style="margin-top:18px">รายการสินค้า/บริการ</div>
        <div class="helper">ปรับปริมาณ/ราคาเป็นค่าลบสำหรับใบลดหนี้ หรือบวกสำหรับใบเพิ่มหนี้</div>
        <table class="fa-table" style="margin-top:10px">
          <thead>
            <tr>
              <th>รายการ</th>
              <th class="qty">Qty</th>
              <th class="price">ราคา/หน่วย</th>
              <th class="qty">หน่วย</th>
              <th class="line">รวม</th>
            </tr>
          </thead>
          <tbody id="cn-items">
            @foreach($items as $idx => $it)
              <tr>
                <td><input name="items[{{ $idx }}][description]" value="{{ $it['description'] ?? '' }}" placeholder="รายละเอียด"></td>
                <td><input class="cn-qty" name="items[{{ $idx }}][qty]" type="number" step="0.01" value="{{ $it['qty'] ?? 1 }}"></td>
                <td><input class="cn-price" name="items[{{ $idx }}][unit_price]" type="number" step="0.01" value="{{ $it['unit_price'] ?? 0 }}"></td>
                <td><input name="items[{{ $idx }}][unit]" value="{{ $it['unit'] ?? '' }}"></td>
                <td><input class="cn-line" name="items[{{ $idx }}][line_total]" type="number" step="0.01" value="{{ $it['line_total'] ?? 0 }}"></td>
              </tr>
            @endforeach
          </tbody>
        </table>
        <button type="button" class="fa-btn" style="margin-top:10px" onclick="addCNRow()">+ เพิ่มรายการ</button>
      </div>

      <div class="fa-card fa-section fa-sticky">
        <div class="section-title">สรุปยอด</div>
        <div class="helper">ยอดรวมจะใช้ตามรายการด้านซ้าย ถ้าปรับเองให้ใส่ตัวเลข</div>
        <div class="fa-totals">
          <div class="row"><span>Subtotal</span><input class="fa-input" id="subtotal" type="number" step="0.01" name="subtotal"
            value="{{ old('subtotal', $isEdit ? $note->subtotal : ($invoice->subtotal ?? 0)) }}" readonly></div>
          <div class="row"><span>Tax</span><input class="fa-input" id="tax" type="number" step="0.01" name="tax"
            value="{{ old('tax', $isEdit ? $note->tax : 0) }}"></div>
          <div class="row"><span><strong>Total</strong></span><input class="fa-input" id="total" type="number" step="0.01"
            name="total" value="{{ old('total', $isEdit ? $note->total : ($invoice->total ?? 0)) }}" readonly></div>
          <div class="row"><span>Currency</span><input class="fa-input" name="currency"
            value="{{ old('currency', $isEdit ? ($note->currency ?? 'THB') : ($invoice->currency ?? 'THB')) }}"></div>
        </div>
      </div>
    </div>
  </div>
</form>
<script>
  const invoices = @json($invoicePayload);

  function addCNRow(rowData = {}){
    const tbody = document.getElementById('cn-items');
    const idx = tbody.children.length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input name="items[${idx}][description]" placeholder="รายละเอียด" value="${rowData.description ?? ''}"></td>
      <td><input class="cn-qty" name="items[${idx}][qty]" type="number" step="0.01" value="${rowData.qty ?? 1}"></td>
      <td><input class="cn-price" name="items[${idx}][unit_price]" type="number" step="0.01" value="${rowData.unit_price ?? 0}"></td>
      <td><input name="items[${idx}][unit]" value="${rowData.unit ?? ''}"></td>
      <td><input class="cn-line" name="items[${idx}][line_total]" type="number" step="0.01" value="${rowData.line_total ?? 0}"></td>
    `;
    tbody.appendChild(tr);
    bindRowEvents(tr);
    recalcTotals();
  }

  function bindRowEvents(tr){
    const qty = tr.querySelector('.cn-qty');
    const price = tr.querySelector('.cn-price');
    const line = tr.querySelector('.cn-line');
    [qty, price].forEach(el => el?.addEventListener('input', () => {
      const q = parseFloat(qty.value) || 0;
      const p = parseFloat(price.value) || 0;
      line.value = (q * p).toFixed(2);
      recalcTotals();
    }));
    line?.addEventListener('input', recalcTotals);
  }

  function recalcTotals(){
    const lines = document.querySelectorAll('.cn-line');
    let subtotal = 0;
    lines.forEach(input => subtotal += parseFloat(input.value) || 0);
    const subtotalInput = document.getElementById('subtotal');
    const taxInput = document.getElementById('tax');
    const totalInput = document.getElementById('total');
    subtotalInput.value = subtotal.toFixed(2);
    const tax = parseFloat(taxInput.value) || 0;
    totalInput.value = (subtotal + tax).toFixed(2);
  }

  function hydrateFromInvoice(id){
    const inv = invoices.find(i => String(i.id) === String(id));
    if (!inv) return;
    document.getElementById('invoice-number').value = inv.number || '';
    document.querySelector('input[name="issue_date"]').value = inv.issue_date || document.querySelector('input[name="issue_date"]').value;
    document.querySelector('input[name="customer_name"]').value = inv.customer_name || '';
    document.querySelector('input[name="customer_tax_id"]').value = inv.customer_tax_id || '';
    document.querySelector('select[name="customer_branch_type"]').value = inv.customer_branch_type || '';
    document.querySelector('input[name="customer_branch_code"]').value = inv.customer_branch_code || '';
    document.querySelector('textarea[name="customer_address"]').value = inv.customer_address || '';
    document.querySelector('input[name="currency"]').value = inv.currency || 'THB';

    const tbody = document.getElementById('cn-items');
    tbody.innerHTML = '';
    (inv.items || []).forEach(it => {
      addCNRow({
        description: it.description,
        qty: it.qty,
        unit_price: it.unit_price,
        unit: it.unit,
        line_total: (it.qty || 0) * (it.unit_price || 0),
      });
    });
    if (!inv.items || inv.items.length === 0) {
      addCNRow();
    }
    document.getElementById('tax').value = inv.tax ?? 0;
    recalcTotals();
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('#cn-items tr').forEach(bindRowEvents);
    document.getElementById('tax')?.addEventListener('input', recalcTotals);
    document.getElementById('invoice-select')?.addEventListener('change', (e) => {
      const val = e.target.value;
      if (val) {
        hydrateFromInvoice(val);
      }
    });
    recalcTotals();
  });
</script>
