@extends('layouts.app')

@section('body')

<style>
:root{--brand:#2B4A72;--ink:#0f172a;--muted:#64748b;--line:#e5e7eb;--bg:#f8fafc;--card:#ffffff}
body{background:var(--bg)}
.fa-wrap{max-width:1160px;margin:0 auto;padding:20px}
.fa-topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.fa-title{font-size:20px;font-weight:700;color:var(--ink)}
.fa-actions{display:flex;gap:8px}
.fa-btn{display:inline-flex;align-items:center;gap:6px;border-radius:10px;border:1px solid var(--line);padding:8px 12px;text-decoration:none;font-weight:600}
.fa-btn.save{background:var(--brand);color:#fff;border-color:var(--brand)}
.fa-btn.light{background:#fff;color:var(--ink)}
.fa-card{background:var(--card);border:1px solid var(--line);border-radius:14px}
.fa-grid{display:grid;grid-template-columns:1fr 340px;gap:16px}
@media (max-width: 992px){.fa-grid{grid-template-columns:1fr}}
.fa-section{padding:16px}
.fa-meta dl{display:grid;grid-template-columns:130px 1fr;gap:8px 12px;margin:0}
.fa-meta dt{color:var(--muted)} .fa-meta dd{margin:0;font-weight:700}
.fa-label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px}
.fa-input,.fa-textarea,.fa-select{width:100%;background:#fff;border:1px solid var(--line);border-radius:10px;padding:9px 10px}
.fa-textarea{min-height:84px}
.fa-two{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:6px}
.fa-two .span-2{grid-column:1/-1}
@media (max-width: 768px){.fa-two{grid-template-columns:1fr}}
.customer-toolbar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;padding-top:4px}
.customer-toolbar .fa-toggle{display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:500;color:var(--muted)}
.customer-toolbar .fa-toggle input{accent-color:var(--brand)}
.fa-inline-actions{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
.fa-btn.micro{padding:6px 12px;font-size:13px;border-radius:8px}
.fa-btn.ghost{background:transparent;color:var(--brand)}
.fa-btn.ghost:hover{background:rgba(43,74,114,0.08)}
.fa-btn[data-loading="1"]{pointer-events:none;opacity:0.7}
.fa-table{width:100%;border-collapse:separate;border-spacing:0 0}
.fa-table thead th{background:var(--brand);color:#fff;border:0;padding:10px 12px;font-weight:700}
.fa-table tbody td{background:#fff;border-bottom:1px solid var(--line);padding:10px 12px;vertical-align:middle}
.fa-table .no{width:64px;text-align:center}
.fa-table .qty,.fa-table .price,.fa-table .line{text-align:right;width:140px}
.fa-sticky{position:sticky;top:16px}
.fa-totals .row{display:flex;justify-content:space-between;margin:6px 0}
.fa-totals .row strong{font-weight:800}
.fa-add{margin-top:8px}
.fa-del{background:#fff;border:1px solid var(--line);border-radius:8px;padding:4px 10px}
.text-right{text-align:right}
.fa-badge{display:inline-block;background:#eef2ff;color:var(--brand);border:1px solid var(--brand);padding:2px 8px;border-radius:999px;font-size:12px}
.stack{display:flex;flex-direction:column;gap:6px}
.alert{border-radius:12px;padding:10px 12px;border:1px solid}
.alert-danger{background:#fff1f2;border-color:#fecdd3;color:#991b1b}
.alert-success{background:#ecfdf5;border-color:#a7f3d0;color:#065f46}
</style>

@php
  $quotation = $quotation ?? null; // กัน null เวลาใช้ร่วมกับหน้า create
  $number   = $nextNumber ?? $provisionalNumber ?? 'QT'.now()->format('Ymd').'-????';
  $taxRate  = old('tax_rate', 0);
  $rows     = old('items', [['id'=>null,'description'=>'','quantity'=>1,'unit_price'=>0]]);
@endphp

<div class="fa-wrap">
  <div class="fa-topbar">
    <div class="fa-title">Create Quotation</div>
    <div class="fa-actions">
      <a href="{{ route('quotations.index') }}" class="fa-btn light">Close</a>
      <button type="submit" class="fa-btn save" form="qForm">Save</button>
    </div>
  </div>

  {{-- แสดง error/success --}}
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
  @if (session('ok'))
    <div class="alert alert-success" role="alert">{{ session('ok') }}</div>
  @endif

  <form id="qForm" method="POST" action="{{ route('quotations.store') }}" autocomplete="off">
    @csrf
    <input type="hidden" name="customer_id" id="customer_id_hidden" value="{{ old('customer_id', optional($quotation)->customer_id) }}">

    <div class="fa-grid">
      {{-- LEFT --}}
      <div class="fa-card fa-section">
        <div style="display:grid;grid-template-columns:1fr;gap:12px">

          {{-- ===== Customer Picker (ใหม่) ===== --}}
          <div class="fa-two" style="align-items:end">
            <div>
              <label class="fa-label">ค้นหาลูกค้า</label>
              <input id="customer_search" type="text" class="fa-input" placeholder="พิมพ์ชื่อ / เลขผู้เสียภาษี">
            </div>
            <div>
              <label class="fa-label">Select Customer</label>
              <select class="fa-select" id="customer_id_select" data-initial="{{ old('customer_id', optional($quotation)->customer_id) }}">
                <option value="">— เลือกลูกค้า —</option>
              </select>
              <div class="form-text" style="font-size:12px;color:var(--muted);margin-top:6px">
                พิมพ์ค้นหา แล้วเลือกเพื่อดึงข้อมูลมาใส่อัตโนมัติ
              </div>
            </div>
            <div class="span-2 customer-toolbar">
              <label class="fa-toggle">
                <input type="checkbox" id="unlockFields">
                <span>ปลดล็อกเพื่อแก้ไขรายละเอียดลูกค้าในเอกสารนี้</span>
              </label>
              <div class="fa-inline-actions">
                <button type="button" class="fa-btn light micro" id="btnCreateContact">
                  <i class="fa fa-user-plus" aria-hidden="true"></i>
                  Create new contact
                </button>
                <button type="button" class="fa-btn ghost micro" id="btnSearchK75">
                  <i class="fa fa-database" aria-hidden="true"></i>
                  Search from the K75 database
                </button>
              </div>
            </div>
          </div>
          {{-- ===== /Customer Picker ===== --}}

          <div>
            <label class="fa-label">Customer Name</label>
            <input id="cust_name" class="fa-input" type="text" name="customer_name"
                   value="{{ old('customer_name', optional($quotation)->customer_name) }}">
          </div>

          <div class="fa-two">
            <div>
              <label class="fa-label">Reference (optional)</label>
              <input class="fa-input" type="text" name="reference"
                     value="{{ old('reference', optional($quotation)->reference) }}"
                     placeholder="PO / Ref No.">
            </div>
            <div>
              <label class="fa-label">Currency</label>
              @php $cur = old('currency', optional($quotation)->currency ?? 'THB'); @endphp
              <select class="fa-select" name="currency">
                <option value="THB" {{ $cur==='THB'?'selected':'' }}>THB (฿)</option>
                <option value="USD" {{ $cur==='USD'?'selected':'' }}>USD ($)</option>
                <option value="EUR" {{ $cur==='EUR'?'selected':'' }}>EUR (€)</option>
                <option value="JPY" {{ $cur==='JPY'?'selected':'' }}>JPY (¥)</option>
              </select>
            </div>

            <div class="span-2">
              <label class="fa-label">Customer Address</label>
              <textarea id="cust_address" name="customer_address" rows="3" class="fa-textarea"
                        placeholder="ที่อยู่ลูกค้า">{{ old('customer_address', optional($quotation)->customer_address) }}</textarea>
            </div>

            <div>
              <label class="fa-label">Tax ID</label>
              <input id="cust_tax" type="text" name="customer_tax_id" class="fa-input"
                     value="{{ old('customer_tax_id', optional($quotation)->customer_tax_id) }}"
                     placeholder="เลขประจำตัวผู้เสียภาษี">
            </div>

            <div>
              <label class="fa-label">Branch Type</label>
              @php $branchType = old('customer_branch_type', optional($quotation)->customer_branch_type ?? ''); @endphp
              <select id="cust_branch_type" name="customer_branch_type" class="fa-select">
                <option value=""       {{ $branchType==='' ? 'selected' : '' }}>—</option>
                <option value="head"   {{ $branchType==='head' ? 'selected' : '' }}>Head Office</option>
                <option value="branch" {{ $branchType==='branch' ? 'selected' : '' }}>Branch</option>
              </select>
            </div>

            <div>
              <label class="fa-label">Branch Code</label>
              <input id="cust_branch_code" type="text" name="customer_branch_code" class="fa-input"
                     value="{{ old('customer_branch_code', optional($quotation)->customer_branch_code) }}"
                     placeholder="เช่น 00000 หรือ 001">
            </div>

            <div>
              <label class="fa-label">Salesperson</label>
              <input type="text" name="salesperson" class="fa-input"
                     value="{{ old('salesperson', optional($quotation)->salesperson) }}"
                     placeholder="พนักงานขาย">
            </div>

            <div>
              <label class="fa-label">Discount (%)</label>
              <input type="number" min="0" step="0.01" name="discount_percent" class="fa-input"
                     value="{{ old('discount_percent', optional($quotation)->discount_percent ?? 0) }}"
                     oninput="recalcTotals()">
            </div>

            <div class="span-2">
              <label class="fa-label">Detail / Notes</label>
              <textarea class="fa-textarea" name="notes"
                        placeholder="ระบุเงื่อนไขการขาย / การชำระเงิน / หมายเหตุ">{{ old('notes', optional($quotation)->notes) }}</textarea>
            </div>
          </div>
        </div>

        <div style="margin-top:14px">
          <table class="fa-table" id="itemsTable">
            <thead>
              <tr>
                <th class="no">No.</th>
                <th>Name / Description</th>
                <th class="qty">Quantity</th>
                <th class="price">Unit Price</th>
                <th class="line">Total</th>
                <th style="width:48px"></th>
              </tr>
            </thead>
            <tbody>
            @foreach($rows as $i => $item)
              @php
                $desc = (string)($item['description'] ?? '');
                $pos = strpos($desc, "\n");
                $nameText  = $pos === false ? trim($desc) : trim(substr($desc,0,$pos));
                $extraText = $pos === false ? '' : trim(substr($desc,$pos+1));
                $qty  = (float)($item['quantity'] ?? $item['qty'] ?? 1);
                $unit = (float)($item['unit_price'] ?? $item['price'] ?? 0);
                $line = $qty * $unit;
              @endphp
              <tr>
                <td class="no">{{ $i+1 }}</td>
                <td>
                  <div class="stack">
                    <input type="hidden" class="desc-hidden" name="items[{{ $i }}][description]" value="{{ $desc }}">
                    <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item['id'] ?? '' }}">
                    <input class="fa-input name-text" type="text" value="{{ $nameText }}" placeholder="Name" autocomplete="off" oninput="combineDesc(this)">
                    <input class="fa-input desc-text" type="text" value="{{ $extraText }}" placeholder="Description (optional)" autocomplete="off" oninput="combineDesc(this)">
                  </div>
                </td>
                <td class="qty">
                  <input class="fa-input qty" type="number" min="0" step="1" name="items[{{ $i }}][quantity]" value="{{ $qty }}" oninput="recalcTotals()">
                </td>
                <td class="price">
                  <input class="fa-input price" type="number" min="0" step="0.01" name="items[{{ $i }}][unit_price]" value="{{ $unit }}" oninput="recalcTotals()">
                </td>
                <td class="line line-total">{{ number_format($line,2) }}</td>
                <td class="text-right">
                  <button type="button" class="fa-del" onclick="this.closest('tr').remove(); renumberRows(); recalcTotals();">×</button>
                </td>
              </tr>
            @endforeach
            </tbody>
          </table>

          <div class="fa-add">
            <button type="button" class="fa-btn light" id="addRow" onclick="addItemRow()">+ Add item</button>
          </div>
        </div>
      </div>

      {{-- RIGHT --}}
      <div class="fa-sticky">
        <div class="fa-card fa-section fa-meta" style="margin-bottom:12px">
          <dl>
            <dt>Quotation No.</dt><dd>{{ $number }}</dd>
            <dt>Status</dt><dd><span class="fa-badge">Draft</span></dd>

            <dt>Date</dt>
            <dd><input class="fa-input" type="date" name="issue_date"
                       value="{{ old('issue_date', now()->format('Y-m-d')) }}"></dd>

            <dt>Valid Until</dt>
            <dd><input class="fa-input" type="date" name="valid_until"
                       value="{{ old('valid_until', optional($quotation)->valid_until) }}"></dd>

            <dt>Enable VAT</dt>
            <dd>
              @php $vatOn = old('vat_enabled', optional($quotation)->vat_enabled) ? true : false; @endphp
              <label style="display:flex;align-items:center;gap:8px">
                <input type="checkbox" name="vat_enabled" value="1"
                       {{ $vatOn ? 'checked' : '' }} onchange="recalcTotals()">
                <span class="fa-label" style="margin:0">Calculate tax from total</span>
              </label>
            </dd>

            <dt>Tax Rate (%)</dt>
            <dd>
              @php $rate = (float)old('tax_rate', optional($quotation)->tax_rate ?? $taxRate); @endphp
              <select class="fa-select" name="tax_rate" onchange="recalcTotals()">
                <option value="0" {{ $rate==0 ? 'selected' : '' }}>0%</option>
                <option value="3" {{ $rate==3 ? 'selected' : '' }}>3%</option>
                <option value="7" {{ $rate==7 ? 'selected' : '' }}>7%</option>
              </select>
            </dd>
          </dl>
        </div>

        <div class="fa-card fa-section fa-totals">
          <div class="fa-title" style="font-size:16px;margin-bottom:10px;color:var(--ink)">Grand Total</div>
          <div class="row"><span>Subtotal</span><strong id="subTotal">0.00</strong></div>
          <div class="row"><span>Discount</span><strong id="discTotal">0.00</strong></div>
          <div class="row"><span>Tax</span><strong id="taxTotal">0.00</strong></div>
          <div class="row" style="border-top:1px dashed var(--line);padding-top:8px">
            <span>Total</span><strong id="grandTotal">0.00</strong>
          </div>
        </div>

      </div>
    </div>

    {{-- hidden totals for validator --}}
    <input type="hidden" name="subtotal"         id="subtotalInput"  value="0">
    <input type="hidden" name="discount_amount"  id="discountInput"  value="0">
    <input type="hidden" name="tax"              id="taxInput"       value="0">
    <input type="hidden" name="total"            id="totalInput"     value="0">

  </form>
</div>

<script>
(function () {
  // ---------- URLs (ชัวร์สุด ไม่พึ่งชื่อ route) ----------
  const OPT_URL  = "{{ url('/api/customers/options') }}";
  const SHOW_URL = "{{ url('/api/customers') }}";

  // ---------- CUSTOMER PICKER ----------
  const selectBox  = document.getElementById('customer_id_select');
  const searchBox  = document.getElementById('customer_search');
  const hiddenId   = document.getElementById('customer_id_hidden');
  const unlockBox  = document.getElementById('unlockFields');
  const newContactBtn = document.getElementById('btnCreateContact');
  const k75SearchBtn  = document.getElementById('btnSearchK75');
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || document.querySelector('meta[name="csrf-token"]')?.content || '';

  const K75_URL = "{{ url('/api/k75/search') }}";

  const fields = {
    name: document.getElementById('cust_name'),
    address: document.getElementById('cust_address'),
    tax: document.getElementById('cust_tax'),
    branchType: document.getElementById('cust_branch_type'),
    branchCode: document.getElementById('cust_branch_code'),
  };

  function setLocked(locked){
    // ใช้ readOnly ทั้งหมด เพื่อให้ submit ได้
    [fields.name, fields.tax, fields.branchCode].forEach(el=>{ if(el) el.readOnly = locked; });
    if(fields.address) fields.address.readOnly = locked;
    // branchType เป็น select: ไม่ปิด disable เพื่อให้ส่งค่าได้
  }
  setLocked(true);
  unlockBox?.addEventListener('change', e=> setLocked(!e.target.checked));

  function buildHeaders(){
    const headers = {'X-Requested-With':'XMLHttpRequest'};
    if(csrfToken){ headers['X-CSRF-TOKEN'] = csrfToken; }
    return headers;
  }

  newContactBtn?.addEventListener('click', ()=>{
    const evt = new CustomEvent('quotation:create-contact', {
      cancelable: true,
      detail: { searchTerm: searchBox?.value || '' }
    });
    if(window.dispatchEvent(evt) === false){
      return;
    }
    window.open("{{ route('customers.create') }}", '_blank', 'noopener');
  });

  function hydrateCustomer(record){
    if(!record) return;
    hiddenId.value = '';
    if(fields.name && record.name){ fields.name.value = record.name; }
    if(fields.address && record.address){ fields.address.value = record.address; }
    if(fields.tax && record.tax_id){ fields.tax.value = record.tax_id; }
    if(fields.branchType){
      const branchType = record.branch_type
        ?? (record.is_branch === true ? 'branch'
          : record.is_branch === false ? 'head' : '');
      if(branchType){ fields.branchType.value = branchType; }
    }
    if(fields.branchCode && record.branch_code){ fields.branchCode.value = record.branch_code; }
  }

  async function searchK75(){
    const query = (searchBox?.value || '').trim();
    if(!query){
      alert('กรุณากรอกคำค้นหาก่อนค้นจากฐานข้อมูล K75');
      searchBox?.focus();
      return;
    }

    const requestDetail = { query };
    const beforeEvt = new CustomEvent('quotation:k75-search', { cancelable: true, detail: requestDetail });
    if(window.dispatchEvent(beforeEvt) === false){
      return;
    }

    const btn = k75SearchBtn;
    if(btn){
      btn.dataset.loading = '1';
    }

    try {
      const res = await fetch(`${K75_URL}?q=${encodeURIComponent(query)}`, {
        headers: buildHeaders(),
      });
      if(!res.ok){
        throw new Error(`K75 search failed with status ${res.status}`);
      }
      const payload = await res.json();
      window.dispatchEvent(new CustomEvent('quotation:k75-results', {
        detail: { query, results: payload }
      }));

      if(Array.isArray(payload) && payload.length === 1){
        hydrateCustomer(payload[0]);
      } else if(payload && payload.name){
        hydrateCustomer(payload);
      } else if(Array.isArray(payload) && payload.length === 0){
        alert('ไม่พบข้อมูลจากฐาน K75 ตามคำค้นหา');
      }
    } catch(err){
      console.error(err);
      alert('ไม่สามารถเชื่อมต่อฐานข้อมูล K75 ได้ในขณะนี้');
    } finally {
      if(btn){
        delete btn.dataset.loading;
      }
    }
  }

  k75SearchBtn?.addEventListener('click', (e)=>{
    e.preventDefault();
    searchK75();
  });

  async function loadCustomerOptions(q=''){
    try {
      const res = await fetch(`${OPT_URL}?q=${encodeURIComponent(q)}`, {
        headers: buildHeaders()
      });
      if(!res.ok) throw new Error('Failed to load customer options');
      const data = await res.json();
      // clear options (keep first)
      [...selectBox.options].slice(1).forEach(o=>o.remove());
      data.forEach(row=>{
        const opt = new Option(row.text, row.id);
        selectBox.add(opt);
      });
    } catch(err){
      console.error(err);
    }
  }

  async function fillCustomer(id){
    if(!id){ hiddenId.value=''; return; }
    try {
      const res = await fetch(`${SHOW_URL}/${id}.json`, {
        headers: buildHeaders()
      });
      if(!res.ok) throw new Error('Customer not found');
      const c = await res.json();
      hiddenId.value = c.id || '';
      if(fields.name)        fields.name.value        = c.name || '';
      if(fields.address)     fields.address.value     = c.address || '';
      if(fields.tax)         fields.tax.value         = c.tax_id || '';
      if(fields.branchType)  fields.branchType.value  = (c.is_branch ? 'branch' : 'head');
      if(fields.branchCode)  fields.branchCode.value  = c.branch_code || '';
    } catch(err){
      console.error(err);
    }
  }

  // ช่องค้นหา (debounce)
  let timer=null;
  searchBox?.addEventListener('input', (e)=>{
    clearTimeout(timer);
    timer=setTimeout(()=> loadCustomerOptions(e.target.value||''), 250);
  });

  selectBox?.addEventListener('change', e=> fillCustomer(e.target.value));

  // initial
  document.addEventListener('DOMContentLoaded', async ()=>{
    await loadCustomerOptions('');
    const initial = selectBox.dataset.initial;
    if (initial) {
      selectBox.value = initial;
      await fillCustomer(initial);
    }
  });

  // ---------- helpers ----------
  function num(el){ if(!el) return 0; const v=String(el.value||'').replace(/,/g,'').trim(); const n=Number(v); return isFinite(n)?n:0; }
  function fmt(n){ return (isNaN(n)?0:n).toFixed(2); }

  // รวม Name + Description เป็น description จริง (ซ่อนใน hidden)
  window.combineDesc = function(el){
    const tr = el.closest('tr');
    const name = (tr.querySelector('.name-text')?.value || '').trim();
    const more = (tr.querySelector('.desc-text')?.value || '').trim();
    const hidden = tr.querySelector('.desc-hidden');
    if(hidden){ hidden.value = more ? (name + "\n" + more) : name; }
  };

  // คำนวณยอด + sync ไปที่กล่องด้านขวา + hidden
  window.recalcTotals = function(){
    const tbody=document.querySelector('#itemsTable tbody'); let sub=0;
    if(tbody){
      tbody.querySelectorAll('tr').forEach(tr=>{
        const q=num(tr.querySelector('input.qty'));
        const p=num(tr.querySelector('input.price'));
        const line=q*p;
        const cell=tr.querySelector('.line-total'); if(cell) cell.textContent=fmt(line);
        sub+=line;
      });
    }

    const discPct = num(document.querySelector('[name="discount_percent"]'));
    const afterDisc = sub * (1 - discPct/100);
    const discountAmt = sub - afterDisc;

    const vatOn = document.querySelector('[name="vat_enabled"]')?.checked;
    const rate  = Number(document.querySelector('[name="tax_rate"]')?.value || 0);
    const tax   = vatOn ? (afterDisc * (rate/100)) : 0;
    const total = afterDisc + tax;

    document.getElementById('subTotal').textContent  = fmt(sub);
    document.getElementById('discTotal').textContent = fmt(discountAmt);
    document.getElementById('taxTotal').textContent  = fmt(tax);
    document.getElementById('grandTotal').textContent= fmt(total);

    document.getElementById('subtotalInput').value = fmt(sub);
    document.getElementById('discountInput').value = fmt(discountAmt);
    document.getElementById('taxInput').value      = fmt(tax);
    document.getElementById('totalInput').value    = fmt(total);
  };

  // เรียงเลขบรรทัด + ซ่อม index ชื่อฟิลด์ items[i][...]
  window.renumberRows = function(){
    const tbody=document.querySelector('#itemsTable tbody'); if(!tbody) return;
    [...tbody.querySelectorAll('tr')].forEach((tr,i)=>{
      const no=tr.querySelector('.no'); if(no) no.textContent=i+1;
      tr.querySelectorAll('input,textarea').forEach(inp=>{
        const m = inp.name && inp.name.match(/^items\[\d+\]/);
        if(m){ inp.name = inp.name.replace(/^items\[\d+\]/, `items[${i}]`); }
      });
    });
  };

  // เพิ่มแถวรายการ
  window.addItemRow = function(){
    const tbody=document.querySelector('#itemsTable tbody');
    const i=tbody.querySelectorAll('tr').length;
    const tr=document.createElement('tr');
    tr.innerHTML=`
      <td class="no">${i+1}</td>
      <td>
        <div class="stack">
          <input type="hidden" class="desc-hidden" name="items[${i}][description]" value="">
          <input type="hidden" name="items[${i}][id]" value="">
          <input class="fa-input name-text" type="text" value="" placeholder="Name" autocomplete="off" oninput="combineDesc(this)">
          <input class="fa-input desc-text" type="text" value="" placeholder="Description (optional)" autocomplete="off" oninput="combineDesc(this)">
        </div>
      </td>
      <td class="qty"><input class="fa-input qty"   type="number" min="0" step="1"    name="items[${i}][quantity]"   value="1" oninput="recalcTotals()"></td>
      <td class="price"><input class="fa-input price" type="number" min="0" step="0.01" name="items[${i}][unit_price]" value="0" oninput="recalcTotals()"></td>
      <td class="line line-total">0.00</td>
      <td class="text-right"><button type="button" class="fa-del" onclick="this.closest('tr').remove(); renumberRows(); recalcTotals();">×</button></td>`;
    tbody.appendChild(tr);
    renumberRows(); recalcTotals();
  };

  // init
  document.addEventListener('DOMContentLoaded', ()=>{
    document.querySelectorAll('#itemsTable tbody tr').forEach(tr=>{
      combineDesc(tr.querySelector('.name-text')||tr.querySelector('.desc-text'));
    });
    renumberRows(); recalcTotals();

    document.querySelector('[name="tax_rate"]')?.addEventListener('change', recalcTotals);
    document.querySelector('[name="vat_enabled"]')?.addEventListener('change', recalcTotals);

    // กันพลาดก่อน submit: รวม description ทุกแถว + คำนวณซ้ำ
    document.getElementById('qForm')?.addEventListener('submit', ()=>{
      document.querySelectorAll('#itemsTable tbody tr').forEach(tr=>{
        combineDesc(tr.querySelector('.name-text')||tr.querySelector('.desc-text'));
      });
      recalcTotals();
    });
  });
})();
</script>
@endsection
