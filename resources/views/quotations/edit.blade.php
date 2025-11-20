@extends('layouts.app')
@section('title','Edit Quotation '.$quotation->number)

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')

  <main>
    <div class="topbar">
      <h2 class="m-0">Edit Quotation {{ $quotation->number }}</h2>
    </div>

    <div class="container-fluid py-3">
      @if ($errors->any())
        <div class="alert alert-danger">
          <div class="fw-600 mb-1">Please fix the following:</div>
          <ul class="m-0 ps-3">
            @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('quotations.update', $quotation) }}" autocomplete="off">
        @csrf
        @method('PUT')
        {{-- เก็บ customer_id ไว้ส่งไปกับฟอร์ม --}}
        <input type="hidden" name="customer_id" id="customer_id_hidden" value="{{ old('customer_id', $quotation->customer_id) }}">

        <div class="row g-3">
          {{-- ซ้าย: รายละเอียด + รายการ --}}
          <div class="col-lg-8">
            <div class="panel">
              <div class="panel-header"><strong>Quotation Details</strong></div>
              <div class="panel-body">

                {{-- ===== Customer Picker (ใหม่) ===== --}}
                <div class="row g-3 align-items-end mb-1">
                  <div class="col-md-6">
                    <label class="form-label">ค้นหาลูกค้า</label>
                    <input id="customer_search" type="text" class="form-control" placeholder="พิมพ์ชื่อ / เลขผู้เสียภาษี"
                           value="{{ old('customer_name', $quotation->customer_name) }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Select Customer</label>
                    <select id="customer_id_select" class="form-select" data-initial="{{ old('customer_id', $quotation->customer_id) }}"
                            data-initial-name="{{ old('customer_name', $quotation->customer_name) }}">
                      <option value="">— เลือกลูกค้า —</option>
                    </select>
                  </div>
                  <div class="col-12 d-flex justify-content-end">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="unlockFields">
                      <label class="form-check-label" for="unlockFields">ปลดล็อกเพื่อแก้ไขรายละเอียดลูกค้าในเอกสารนี้</label>
                    </div>
                  </div>
                </div>
                {{-- ===== /Customer Picker ===== --}}

                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label required">Customer Name</label>
                    <input id="cust_name" name="customer_name" class="form-control" required
                           value="{{ old('customer_name', $quotation->customer_name) }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Reference</label>
                    <input name="reference" class="form-control"
                           value="{{ old('reference', $quotation->reference) }}">
                  </div>

                  <div class="col-md-12">
                    <label class="form-label">Customer Address</label>
                    <textarea id="cust_address" name="customer_address" rows="2" class="form-control">{{ old('customer_address', $quotation->customer_address) }}</textarea>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Tax ID</label>
                    <input id="cust_tax" name="customer_tax_id" class="form-control"
                           value="{{ old('customer_tax_id', $quotation->customer_tax_id) }}">
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Branch Type</label>
                    <select id="cust_branch_type" name="customer_branch_type" class="form-select">
                      @foreach(($branchTypes ?? ['-'=>'—','head'=>'HeadOffice','branch'=>'Branch']) as $k=>$v)
                        <option value="{{ $k }}" @selected(old('customer_branch_type',$quotation->customer_branch_type)==$k)>{{ $v }}</option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Branch Code</label>
                    <input id="cust_branch_code" name="customer_branch_code" class="form-control"
                           value="{{ old('customer_branch_code', $quotation->customer_branch_code) }}">
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Salesperson</label>
                    <input name="salesperson" class="form-control"
                           value="{{ old('salesperson', $quotation->salesperson) }}">
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Currency</label>
                    <select name="currency" class="form-select">
                      @foreach(($currencies ?? ['THB'=>'THB (฿)','USD'=>'USD ($)']) as $k=>$v)
                        <option value="{{ $k }}" @selected(old('currency',$quotation->currency)==$k)>{{ $v }}</option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                      @foreach(($statuses ?? ['draft'=>'Draft','approved'=>'Approved','rejected'=>'Rejected']) as $k=>$v)
                        <option value="{{ $k }}" @selected(old('status',$quotation->status)==$k)>{{ $v }}</option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Issue Date</label>
                    <input type="date" name="issue_date" class="form-control"
                           value="{{ old('issue_date', optional($quotation->issue_date)->format('Y-m-d')) }}">
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Valid Until</label>
                    <input type="date" name="valid_until" class="form-control"
                           value="{{ old('valid_until', optional($quotation->valid_until)->format('Y-m-d')) }}">
                  </div>

                  <div class="col-md-2">
                    <label class="form-label">Tax Rate (%)</label>
                    <input type="number" step="0.01" name="tax_rate" class="form-control"
                           value="{{ old('tax_rate', $quotation->tax_rate) }}">
                  </div>

                  <div class="col-md-2">
                    <label class="form-label">Discount (%)</label>
                    <input type="number" step="0.01" name="discount_percent" class="form-control"
                           value="{{ old('discount_percent', $quotation->discount_percent) }}">
                  </div>

                  <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" name="vat_enabled" id="enable_vat" value="1"
                             {{ old('vat_enabled', $quotation->vat_enabled) ? 'checked' : '' }}>
                      <label for="enable_vat" class="form-check-label">Enable VAT</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {{-- รายการสินค้า --}}
            <div class="panel mt-3">
              <div class="panel-header d-flex align-items-center">
                <strong>Items</strong>
                <button type="button" class="btn btn-soft btn-sm ms-auto" onclick="addItemRow()">+ Add Item</button>
              </div>
              <div class="panel-body p-0">
                @php
                  $rows = old('items', isset($quotation->items) ? $quotation->items->toArray() : []);
                  if (!is_array($rows) || !count($rows)) {
                    $rows = [['description'=>'','qty'=>1,'unit'=>'','price'=>0,'discount'=>0]];
                  }
                @endphp
                <table class="table m-0" id="itemTable">
                  <thead>
                    <tr>
                      <th style="width:40%">Description</th>
                      <th style="width:10%">Qty</th>
                      <th style="width:10%">Unit</th>
                      <th style="width:15%">Price</th>
                      <th style="width:15%">Discount</th>
                      <th style="width:10%"></th>
                    </tr>
                  </thead>
                  <tbody>
                  @foreach($rows as $i => $it)
                    <tr class="item-row">
                      <td><input class="form-control" name="items[{{ $i }}][description]" value="{{ $it['description'] ?? '' }}"></td>
                      <td><input class="form-control" type="number" step="1" min="0" name="items[{{ $i }}][qty]" value="{{ $it['qty'] ?? $it['quantity'] ?? 1 }}"></td>
                      <td><input class="form-control" name="items[{{ $i }}][unit]" value="{{ $it['unit'] ?? '' }}"></td>
                      <td><input class="form-control" type="number" step="0.01" name="items[{{ $i }}][price]" value="{{ $it['price'] ?? $it['unit_price'] ?? 0 }}"></td>
                      <td><input class="form-control" type="number" step="0.01" name="items[{{ $i }}][discount]" value="{{ $it['discount'] ?? 0 }}"></td>
                      <td class="text-center"><button type="button" class="btn btn-soft btn-sm" onclick="removeRow(this)">–</button></td>
                    </tr>
                  @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          {{-- ขวา: สรุป + ปุ่ม --}}
          <div class="col-lg-4">
            <div class="panel">
              <div class="panel-header"><strong>Summary</strong></div>
              <div class="panel-body">
                <div class="mb-3">
                  <div class="text-muted">Quotation No.</div>
                  <div class="fw-600">{{ $quotation->number }}</div>
                </div>

                <div class="card p-3">
                  <div class="d-flex justify-content-between"><span>Subtotal</span><span id="sum-subtotal" data-initial="{{ number_format((float) $quotation->subtotal, 2, '.', '') }}">0.00</span></div>
                  <div class="d-flex justify-content-between"><span>Tax</span><span id="sum-tax" data-initial="{{ number_format((float) $quotation->tax, 2, '.', '') }}">0.00</span></div>
                  <div class="d-flex justify-content-between fw-600"><span>Total</span><span id="sum-total" data-initial="{{ number_format((float) $quotation->total, 2, '.', '') }}">0.00</span></div>
                </div>
              </div>

              <div class="panel-footer d-flex gap-2 justify-content-end">
                <a href="{{ route('quotations.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button class="btn btn-primary">Update</button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </main>
</div>

@push('scripts')
<script>
// ---------- URLs สำหรับ API ลูกค้า ----------
const OPT_URL  = "{{ url('/api/customers/options') }}";
const SHOW_URL = "{{ url('/api/customers') }}";

// ---------- CUSTOMER PICKER ----------
(function(){
  const selectBox = document.getElementById('customer_id_select');
  const searchBox = document.getElementById('customer_search');
  const hiddenId  = document.getElementById('customer_id_hidden');
  const unlockBox = document.getElementById('unlockFields');
  const initialName = selectBox?.dataset.initialName || '';

  const fields = {
    name: document.getElementById('cust_name'),
    address: document.getElementById('cust_address'),
    tax: document.getElementById('cust_tax'),
    branchType: document.getElementById('cust_branch_type'),
    branchCode: document.getElementById('cust_branch_code'),
  };

  function setLocked(locked){
    [fields.name, fields.tax, fields.branchCode].forEach(el=>{ if(el) el.readOnly = locked; });
    if(fields.address) fields.address.readOnly = locked;
    // branchType เป็น select: ไม่ disable เพื่อให้ส่งค่าได้
  }
  setLocked(true);
  unlockBox?.addEventListener('change', e=> setLocked(!e.target.checked));

  async function loadCustomerOptions(q=''){
    const res = await fetch(`${OPT_URL}?q=${encodeURIComponent(q)}`, { headers: {'X-Requested-With':'XMLHttpRequest'} });
    if(!res.ok) return;
    const data = await res.json();
    [...selectBox.options].slice(1).forEach(o=>o.remove());
    data.forEach(row => selectBox.add(new Option(row.text, row.id)));
    // ensure current customer shows up evenถ้า response ไม่มี
    if(selectBox.dataset.initial && ![...selectBox.options].some(o=>o.value===selectBox.dataset.initial)){
      selectBox.add(new Option(initialName || 'เลือกไว้ก่อนหน้า', selectBox.dataset.initial));
    }
  }

  async function fillCustomer(id){
    if(!id){ hiddenId.value=''; return; }
    const res = await fetch(`${SHOW_URL}/${id}.json`, { headers: {'X-Requested-With':'XMLHttpRequest'} });
    if(!res.ok) return;
    const c = await res.json();
    hiddenId.value = c.id || '';
    if(fields.name)        fields.name.value        = c.name || '';
    if(fields.address)     fields.address.value     = c.address || '';
    if(fields.tax)         fields.tax.value         = c.tax_id || '';
    if(fields.branchType)  fields.branchType.value  = (c.is_branch ? 'branch' : 'head');
    if(fields.branchCode)  fields.branchCode.value  = c.branch_code || '';
  }

  // search debounce
  let t=null;
  searchBox?.addEventListener('input', e=>{
    clearTimeout(t);
    t=setTimeout(()=> loadCustomerOptions(e.target.value||''), 250);
  });
  selectBox?.addEventListener('change', e=> fillCustomer(e.target.value));

  document.addEventListener('DOMContentLoaded', async ()=>{
    await loadCustomerOptions('');
    const initial = selectBox.dataset.initial;
    if(initial){
      selectBox.value = initial;
      await fillCustomer(initial);
      if(searchBox && initialName && !searchBox.value){
        searchBox.value = initialName;
      }
    }
  });
})();

// ---------- เพิ่ม/ลบแถว ----------
function addItemRow(){
  const tb = document.querySelector('#itemTable tbody');
  const idx = tb.querySelectorAll('tr').length;
  tb.insertAdjacentHTML('beforeend', `
    <tr class="item-row">
      <td><input class="form-control" name="items[${idx}][description]"></td>
      <td><input class="form-control" type="number" step="1" min="0" name="items[${idx}][qty]" value="1"></td>
      <td><input class="form-control" name="items[${idx}][unit]"></td>
      <td><input class="form-control" type="number" step="0.01" name="items[${idx}][price]" value="0"></td>
      <td><input class="form-control" type="number" step="0.01" name="items[${idx}][discount]" value="0"></td>
      <td class="text-center"><button type="button" class="btn btn-soft btn-sm" onclick="removeRow(this)">–</button></td>
    </tr>
  `);
  wireSum();
}
function removeRow(btn){ btn.closest('tr').remove(); wireSum(); }

// ---------- คำนวณยอดโชว์คร่าว ๆ ----------
function calc(){
  const rows = document.querySelectorAll('#itemTable tbody tr');
  let sub = 0;
  rows.forEach(tr=>{
    const q = parseFloat(tr.querySelector('[name*="[qty]"]').value||0);
    const p = parseFloat(tr.querySelector('[name*="[price]"]').value||0);
    const d = parseFloat(tr.querySelector('[name*="[discount]"]').value||0);
    sub += (q*p) - d;
  });
  const rate = parseFloat(document.querySelector('[name="tax_rate"]').value||0);
  const enable = document.getElementById('enable_vat')?.checked;
  const tax = enable ? sub * (rate/100) : 0;
  document.getElementById('sum-subtotal').textContent = sub.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  document.getElementById('sum-tax').textContent      = tax.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  document.getElementById('sum-total').textContent    = (sub+tax).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
}
function wireSum(){
  document.querySelectorAll('#itemTable input, [name="tax_rate"], #enable_vat')
    .forEach(el=>el.removeEventListener?.('input',calc) || el.addEventListener('input',calc));
  const initSub = document.getElementById('sum-subtotal').dataset.initial;
  const initTax = document.getElementById('sum-tax').dataset.initial;
  const initTot = document.getElementById('sum-total').dataset.initial;
  if(initSub){
    document.getElementById('sum-subtotal').textContent = Number(initSub).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  }
  if(initTax){
    document.getElementById('sum-tax').textContent = Number(initTax).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  }
  if(initTot){
    document.getElementById('sum-total').textContent = Number(initTot).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
  }
  calc();
}
document.addEventListener('DOMContentLoaded', wireSum);
</script>
@endpush
@endsection
