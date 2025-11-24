@extends('layouts.app')
@section('title','Edit Invoice '.$invoice->number)

@section('body')
<style>
:root{--brand:#31689E;--ink:#0f172a;--muted:#6b7280;--line:#e5e7eb;--bg:#f8fafc;--card:#ffffff}
body{background:var(--bg);color:var(--ink)}
.fa-wrap{max-width:1220px;margin:0 auto;padding:18px 18px 28px}
.fa-topbar{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;gap:16px}
.fa-title{font-size:22px;font-weight:800;color:var(--ink);letter-spacing:-0.01em;margin-bottom:6px}
.fa-subtitle{color:var(--muted);font-size:13px;margin-bottom:6px}
.fa-badge{display:inline-flex;align-items:center;gap:8px;background:#e9f2fb;color:var(--brand);border:1px solid #c5dbf1;padding:4px 12px;border-radius:999px;font-weight:700;font-size:12px}
.fa-number{font-size:18px;font-weight:800;color:var(--brand);letter-spacing:0.04em}
.fa-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.fa-btn{display:inline-flex;align-items:center;gap:6px;border-radius:10px;border:1px solid var(--line);padding:10px 14px;text-decoration:none;font-weight:700;box-shadow:0 8px 20px -15px rgba(15,23,42,0.2);transition:all .15s ease;background:#fff;color:var(--ink)}
.fa-btn.save{background:var(--brand);color:#fff;border-color:var(--brand);box-shadow:0 10px 25px -18px rgba(49,104,158,0.9)}
.fa-btn.ghost{background:#f1f5f9;color:var(--ink);border-color:#d9e3ef}
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
.fa-sticky{position:sticky;top:12px;display:flex;flex-direction:column;gap:12px}
.fa-totals .row{display:flex;justify-content:space-between;margin:7px 0;font-size:14px}
.fa-totals .row strong{font-weight:800;color:var(--ink)}
.helper{color:var(--muted);font-size:12px;margin-top:4px}
.alert{border-radius:12px;padding:10px 12px;border:1px solid;margin-bottom:12px}
.alert-danger{background:#fff1f2;border-color:#fecdd3;color:#991b1b}
.fa-hint{color:var(--muted);font-size:12px;margin-top:6px}
</style>

@php
  $statusOptions = $statusOptions ?? [
    'pending'   => 'Pending / Waiting for Approval',
    'approved'  => 'Approved',
    'paid'      => 'Paid',
    'cancelled' => 'Cancelled / Void',
  ];
  $statusValue = old('status', $invoice->status ?? 'pending');
  $itemsFromOld = old('items');
  if ($itemsFromOld === null) {
    $itemsFromOld = $invoice->items?->map(function($it){
      return [
        'description' => $it->description,
        'qty'         => $it->qty ?? $it->quantity ?? 1,
        'unit_price'  => $it->unit_price ?? $it->price ?? 0,
      ];
    })->toArray() ?? [];
  }
  if (!count($itemsFromOld)) $itemsFromOld = [['description'=>'','qty'=>1,'unit_price'=>0]];
@endphp

<div class="fa-wrap">
  <div class="fa-topbar">
    <div>
      <div class="fa-subtitle">Invoice</div>
      <div class="fa-title">แก้ไขใบแจ้งหนี้</div>
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span class="fa-number">{{ old('number', $invoice->number) }}</span>
        <span class="fa-badge">{{ strtoupper($statusOptions[$statusValue] ?? $statusValue) }}</span>
      </div>
    </div>
    <div class="fa-actions">
      <a href="{{ route('invoices.index') }}" class="fa-btn ghost">ย้อนกลับ</a>
      <button type="submit" form="invForm" class="fa-btn save">บันทึกการแก้ไข</button>
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

  <form id="invForm" method="POST" action="{{ route('invoices.update', $invoice) }}" autocomplete="off">
    @csrf
    @method('PUT')
    <input type="hidden" name="customer_id" id="customer_id_hidden" value="{{ old('customer_id', $invoice->customer_id) }}">

    <div class="fa-grid">
      {{-- LEFT --}}
      <div class="fa-card fa-section">
        <div class="section-title">ข้อมูลลูกค้า &amp; เอกสาร</div>

        <div class="fa-two" style="align-items:end">
          <div class="span-2">
            <label class="fa-label">Select Customer</label>
            <select class="fa-select" id="customer_id_select" data-initial="{{ old('customer_id', $invoice->customer_id) }}">
              <option value="">— เลือกลูกค้า —</option>
            </select>
            <div class="helper">เลือกจากรายชื่อลูกค้าที่มีแล้วระบบจะเติมข้อมูลอัตโนมัติ</div>
          </div>
          <div class="span-2" style="display:flex;justify-content:flex-end;gap:10px">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="checkbox" id="unlockFields">
              <span class="fa-label" style="margin:0">ปลดล็อกเพื่อแก้ไขรายละเอียดลูกค้า</span>
            </label>
          </div>
        </div>

        <div class="fa-two">
          <div>
            <label class="fa-label">Invoice No.</label>
            <input name="number" class="fa-input" value="{{ old('number', $invoice->number) }}" placeholder="แก้เลขได้ตามต้องการ">
            <div class="helper">ปล่อยว่างถ้าใช้เลขเดิม</div>
          </div>
          <div>
            <label class="fa-label">Status</label>
            <select name="status" class="fa-select">
              @foreach($statusOptions as $key => $label)
                <option value="{{ $key }}" {{ $statusValue === $key ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
            <div class="helper">อัปเดตสถานะได้ทันที</div>
          </div>
        </div>

        <div class="fa-two">
          <div>
            <label class="fa-label">Customer Name</label>
            <input id="cust_name" class="fa-input" type="text" name="customer_name" value="{{ old('customer_name', $invoice->customer_name) }}">
          </div>
          <div>
            <label class="fa-label">Tax ID</label>
            <input id="cust_tax" class="fa-input" type="text" name="customer_tax_id" value="{{ old('customer_tax_id', $invoice->customer_tax_id) }}" placeholder="เลขประจำตัวผู้เสียภาษี">
          </div>

          <div>
            <label class="fa-label">Customer Address</label>
            <textarea id="cust_address" name="customer_address" rows="3" class="fa-textarea" placeholder="ที่อยู่ลูกค้า">{{ old('customer_address', $invoice->customer_address) }}</textarea>
          </div>
          <div>
            <label class="fa-label">Branch</label>
            @php $bt = old('customer_branch_type', $invoice->customer_branch_type ?? ''); @endphp
            <div class="fa-two" style="gap:10px; margin-top:0;">
              <select id="cust_branch_type" name="customer_branch_type" class="fa-select">
                <option value="" {{ $bt===''?'selected':'' }}>—</option>
                <option value="head" {{ $bt==='head'?'selected':'' }}>Head Office</option>
                <option value="branch" {{ $bt==='branch'?'selected':'' }}>Branch</option>
              </select>
              <input id="cust_branch_code" name="customer_branch_code" class="fa-input" value="{{ old('customer_branch_code', $invoice->customer_branch_code) }}" placeholder="เช่น 00000 หรือ 001">
            </div>
          </div>
        </div>

        <div class="fa-two">
          <div>
            <label class="fa-label">Issue Date</label>
            <input type="date" name="issue_date" class="fa-input" value="{{ old('issue_date', optional($invoice->issue_date)->toDateString()) }}">
          </div>
          <div>
            <label class="fa-label">Due Date</label>
            <input type="date" name="due_date" class="fa-input" value="{{ old('due_date', optional($invoice->due_date)->toDateString()) }}">
          </div>
        </div>

        <div class="fa-two">
          <div>
            <label class="fa-label">Discount (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="discount_percent" class="fa-input" value="{{ old('discount_percent', $invoice->discount_percent ?? 0) }}">
          </div>
          <div>
            <label class="fa-label">Tax Rate (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="tax_rate" class="fa-input" value="{{ old('tax_rate', $invoice->tax_rate ?? 0) }}">
            <label style="display:flex;align-items:center;gap:8px;margin-top:8px;cursor:pointer;">
              <input class="form-check-input" type="checkbox" id="vat_enabled" name="vat_enabled" style="width:18px;height:18px" {{ old('vat_enabled', $invoice->vat_enabled) ? 'checked' : '' }}>
              <span class="helper" style="margin:0">Enable VAT</span>
            </label>
          </div>
        </div>

        <div style="margin-top:18px">
          <div class="section-title" style="margin-bottom:6px">Items</div>
          <div class="helper">กด Enter เพื่อเพิ่มแถวใหม่ได้ และสามารถลบ/แก้ไขตัวเลขได้ทันที</div>
          <div class="fa-table" style="margin-top:10px">
            <table class="fa-table" id="itemsTable">
              <thead>
                <tr>
                  <th>Description</th>
                  <th class="qty">Qty</th>
                  <th class="price">Unit Price</th>
                  <th class="line">Line Total</th>
                  <th style="width:52px"></th>
                </tr>
              </thead>
              <tbody>
                @foreach($itemsFromOld as $i => $row)
                  <tr>
                    <td><input class="fa-input" name="items[{{ $i }}][description]" value="{{ $row['description'] }}"></td>
                    <td><input type="number" step="0.01" class="fa-input qty" name="items[{{ $i }}][qty]" value="{{ $row['qty'] }}"></td>
                    <td><input type="number" step="0.01" class="fa-input price" name="items[{{ $i }}][unit_price]" value="{{ $row['unit_price'] ?? '' }}"></td>
                    <td><input class="fa-input line-total" value="0.00" readonly></td>
                    <td style="text-align:center"><button type="button" class="fa-btn ghost remove-row" style="padding:6px 10px;border-radius:8px">ลบ</button></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div style="display:flex;justify-content:flex-end;margin-top:10px">
            <button type="button" class="fa-btn ghost" id="addRow">+ Add row</button>
          </div>
        </div>
      </div>

      {{-- RIGHT --}}
      <div class="fa-sticky">
        <div class="fa-card fa-section fa-totals">
          <div class="section-title" style="margin-bottom:8px">Summary</div>
          <div class="row"><span>Invoice No.</span><strong>{{ $invoice->number ?? '-' }}</strong></div>
          <div class="row"><span>Status</span><strong>{{ $statusOptions[$statusValue] ?? $statusValue }}</strong></div>
          <div class="row"><span>Issue Date</span><strong>{{ optional($invoice->issue_date)->format('Y-m-d') }}</strong></div>
          <div class="row"><span>Due Date</span><strong>{{ optional($invoice->due_date)->format('Y-m-d') }}</strong></div>
          <div class="row"><span>ยอดรวมเดิม</span><strong>{{ number_format($invoice->total ?? 0,2) }}</strong></div>
          <input type="hidden" name="total" id="totalField" value="{{ old('total', $invoice->total ?? 0) }}">
        </div>
        <div class="fa-hint">กด “บันทึกการแก้ไข” เพื่ออัปเดตสถานะและข้อมูลทั้งหมด</div>
      </div>
    </div>
  </form>
</div>

@push('scripts')
<script>
(function(){
  const OPT_URL  = "{{ url('/api/customers/options') }}";
  const SHOW_URL = "{{ url('/api/customers') }}";

  const selectBox  = document.getElementById('customer_id_select');
  const hiddenId   = document.getElementById('customer_id_hidden');
  const unlockBox  = document.getElementById('unlockFields');

  const fields = {
    name: document.getElementById('cust_name'),
    address: document.getElementById('cust_address'),
    tax: document.getElementById('cust_tax'),
    branchType: document.getElementById('cust_branch_type'),
    branchCode: document.getElementById('cust_branch_code'),
  };

  function setLocked(locked){
    [fields.name, fields.tax, fields.branchCode, fields.address].forEach(el=>{ if(el) el.readOnly = locked; });
  }
  setLocked(true);
  unlockBox?.addEventListener('change', e=> setLocked(!e.target.checked));

  async function loadCustomerOptions(){
    try{
      const res = await fetch(`${OPT_URL}`, { headers:{'X-Requested-With':'XMLHttpRequest'} });
      if(!res.ok) throw new Error('options failed');
      const data = await res.json();
      [...selectBox.options].slice(1).forEach(o=>o.remove());
      data.forEach(row=> selectBox.add(new Option(row.text, row.id)));
    }catch(err){ console.error(err); }
  }

  async function fillCustomer(id){
    if(!id){ hiddenId.value=''; return; }
    try{
      const res = await fetch(`${SHOW_URL}/${id}.json`, { headers:{'X-Requested-With':'XMLHttpRequest'} });
      if(!res.ok) throw new Error('customer failed');
      const c = await res.json();
      hiddenId.value = c.id || '';
      if(fields.name)        fields.name.value        = c.name || '';
      if(fields.address)     fields.address.value     = c.address || '';
      if(fields.tax)         fields.tax.value         = c.tax_id || '';
      if(fields.branchType)  fields.branchType.value  = (c.is_branch ? 'branch' : 'head');
      if(fields.branchCode)  fields.branchCode.value  = c.branch_code || '';
    }catch(err){ console.error(err); }
  }

  selectBox?.addEventListener('change', e=> fillCustomer(e.target.value));

  document.addEventListener('DOMContentLoaded', async ()=>{
    await loadCustomerOptions();
    const initial = selectBox.dataset.initial;
    if(initial){ selectBox.value = initial; await fillCustomer(initial); }
  });

  const table=document.querySelector('#itemsTable tbody');
  const addBtn=document.getElementById('addRow');
  const totalField=document.getElementById('totalField');

  function recalc(){
    let sum=0;
    table.querySelectorAll('tr').forEach(tr=>{
      const q=parseFloat(tr.querySelector('.qty')?.value||0);
      const p=parseFloat(tr.querySelector('.price')?.value||0);
      const line=q*p;
      tr.querySelector('.line-total').value=line.toFixed(2);
      sum+=line;
    });
    if(totalField) totalField.value=sum.toFixed(2);
  }
  table.addEventListener('input',recalc);

  addBtn.addEventListener('click',()=>{
    const i=table.children.length;
    const tr=document.createElement('tr');
    tr.innerHTML = `
      <td><input class="fa-input" name=\"items[${i}][description]\"></td>
      <td><input type=\"number\" step=\"0.01\" class=\"fa-input qty\" name=\"items[${i}][qty]\"></td>
      <td><input type=\"number\" step=\"0.01\" class=\"fa-input price\" name=\"items[${i}][unit_price]\"></td>
      <td><input class=\"fa-input line-total\" value=\"0.00\" readonly></td>
      <td style=\"text-align:center\"><button type=\"button\" class=\"fa-btn ghost remove-row\" style=\"padding:6px 10px;border-radius:8px\">ลบ</button></td>`;
    table.appendChild(tr); recalc();
  });

  table.addEventListener('click',(e)=>{
    if(e.target.closest('.remove-row')){
      e.target.closest('tr').remove(); recalc();
    }
  });

  document.getElementById('invForm').addEventListener('keydown', (e)=>{
    if(e.key==='Enter' && document.activeElement.closest('#itemsTable')){
      e.preventDefault(); addBtn.click();
    }
  });

  recalc();
})();
</script>
@endpush
@endsection
