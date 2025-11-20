@extends('layouts.app')
@section('title','Edit Invoice '.$invoice->number)

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')

  <main>
    <div class="topbar">
      <h2 class="m-0">Edit Invoice {{ $invoice->number }}</h2>
      <div class="ms-auto"></div>
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

      <form class="panel" method="POST" action="{{ route('invoices.update', $invoice) }}" autocomplete="off">
        @csrf
        @method('PUT')
        <input type="hidden" name="customer_id" id="customer_id_hidden" value="{{ old('customer_id', $invoice->customer_id) }}">

        <div class="panel-header d-flex align-items-center">
          <strong>Invoice details</strong>
        </div>

        <div class="panel-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Invoice No.</label>
              <input name="number" class="form-control" value="{{ old('number', $invoice->number) }}" placeholder="แก้เลขได้ตามต้องการ">
              <div class="form-text">ปรับเลข INV ได้ หรือปล่อยว่างเพื่อใช้เลขเดิม</div>
            </div>

            {{-- ===== Customer Picker ===== --}}
            <div class="col-md-8">
              <label class="form-label">Select Customer</label>
              <select id="customer_id_select" class="form-select" data-initial="{{ old('customer_id', $invoice->customer_id) }}">
                <option value="">— เลือกลูกค้า —</option>
              </select>
              <div class="form-text">เลือกชื่อลูกค้าเพื่อดึงข้อมูลลูกค้ามาใส่อัตโนมัติ</div>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check ms-auto">
                <input class="form-check-input" type="checkbox" id="unlockFields">
                <label class="form-check-label" for="unlockFields">ปลดล็อกเพื่อแก้ไขรายละเอียดลูกค้าในใบนี้</label>
              </div>
            </div>
            {{-- ===== /Customer Picker ===== --}}

            <div class="col-md-6">
              <label class="form-label">Customer Name</label>
              <input id="cust_name" name="customer_name"
                     class="form-control @error('customer_name') is-invalid @enderror"
                     value="{{ old('customer_name', $invoice->customer_name) }}" required>
              @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
              <label class="form-label">Issue Date</label>
              <input type="date" name="issue_date"
                     class="form-control @error('issue_date') is-invalid @enderror"
                     value="{{ old('issue_date', optional($invoice->issue_date)->toDateString()) }}" required>
              @error('issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3">
              <label class="form-label">Due Date</label>
              <input type="date" name="due_date"
                     class="form-control @error('due_date') is-invalid @enderror"
                     value="{{ old('due_date', optional($invoice->due_date)->toDateString()) }}">
              @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            {{-- auto-fill fields --}}
            <div class="col-md-8">
              <label class="form-label">Customer Address</label>
              <textarea id="cust_address" name="customer_address" class="form-control" rows="2"
                        placeholder="ที่อยู่ลูกค้า">{{ old('customer_address', $invoice->customer_address) }}</textarea>
            </div>
            <div class="col-md-4">
              <label class="form-label">Tax ID</label>
              <input id="cust_tax" name="customer_tax_id" class="form-control"
                     value="{{ old('customer_tax_id', $invoice->customer_tax_id) }}" placeholder="เลขประจำตัวผู้เสียภาษี">
            </div>

            <div class="col-md-4">
              <label class="form-label">Branch Type</label>
              @php $bt = old('customer_branch_type', $invoice->customer_branch_type ?? ''); @endphp
              <select id="cust_branch_type" name="customer_branch_type" class="form-select">
                <option value="" {{ $bt===''?'selected':'' }}>—</option>
                <option value="head" {{ $bt==='head'?'selected':'' }}>Head Office</option>
                <option value="branch" {{ $bt==='branch'?'selected':'' }}>Branch</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Branch Code</label>
              <input id="cust_branch_code" name="customer_branch_code" class="form-control"
                     value="{{ old('customer_branch_code', $invoice->customer_branch_code) }}" placeholder="เช่น 00000 หรือ 001">
            </div>

            <div class="col-md-4">
              <label class="form-label">Discount (%)</label>
              <input type="number" step="0.01" min="0" max="100" name="discount_percent"
                     class="form-control" value="{{ old('discount_percent', $invoice->discount_percent ?? 0) }}">
            </div>
            <div class="col-md-4">
              <label class="form-label">Tax Rate (%)</label>
              <input type="number" step="0.01" min="0" max="100" name="tax_rate"
                     class="form-control" value="{{ old('tax_rate', $invoice->tax_rate ?? 0) }}">
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="vat_enabled" name="vat_enabled"
                       {{ old('vat_enabled', $invoice->vat_enabled) ? 'checked' : '' }}>
                <label class="form-check-label" for="vat_enabled">Enable VAT</label>
              </div>
            </div>
          </div>

          <hr class="my-4">

          {{-- รายการสินค้า --}}
          <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>Items</strong>
            <button type="button" class="btn btn-light btn-sm" id="addRow"><i class="bi bi-plus-lg"></i> Add row</button>
          </div>

          <div class="table-responsive">
            <table class="table" id="itemsTable">
              <thead class="table-light">
                <tr>
                  <th>Description</th>
                  <th style="width:120px">Qty</th>
                  <th style="width:160px">Unit Price</th>
                  <th style="width:160px">Line Total</th>
                  <th style="width:52px"></th>
                </tr>
              </thead>
              <tbody>
                @php
                  $oldItems = old('items');
                  if ($oldItems === null) {
                    $oldItems = $invoice->items?->map(function($it){
                      return [
                        'description' => $it->description,
                        'qty'         => $it->qty ?? $it->quantity ?? 1,
                        'unit_price'  => $it->unit_price ?? $it->price ?? 0,
                      ];
                    })->toArray() ?? [];
                  }
                  if (!count($oldItems)) $oldItems = [['description'=>'','qty'=>1,'unit_price'=>0]];
                @endphp
                @foreach($oldItems as $i => $row)
                  <tr>
                    <td><input class="form-control" name="items[{{ $i }}][description]" value="{{ $row['description'] }}"></td>
                    <td><input type="number" step="0.01" class="form-control qty" name="items[{{ $i }}][qty]" value="{{ $row['qty'] }}"></td>
                    <td><input type="number" step="0.01" class="form-control price" name="items[{{ $i }}][unit_price]" value="{{ $row['unit_price'] ?? '' }}"></td>
                    <td><input class="form-control line-total" value="0.00" readonly></td>
                    <td><button type="button" class="btn btn-light btn-sm remove-row"><i class="bi bi-x-lg"></i></button></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="text-end mt-3">
            <a href="{{ route('invoices.index') }}" class="btn btn-light">Cancel</a>
            <button class="btn btn-brand px-4">Save Changes</button>
          </div>
        </div>
      </form>
    </div>
  </main>
</div>

@push('scripts')
<script>
(function(){
  // ---------- URLs (ไม่พึ่งชื่อ route) ----------
  const OPT_URL  = "{{ url('/api/customers/options') }}";
  const SHOW_URL = "{{ url('/api/customers') }}";

  // ---------- CUSTOMER PICKER ----------
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
    [fields.name, fields.tax, fields.branchCode].forEach(el=>{ if(el) el.readOnly = locked; });
    if(fields.address) fields.address.readOnly = locked;
    // ไม่ disable select เพื่อให้ค่าส่งไปกับฟอร์ม
  }
  setLocked(true);
  unlockBox?.addEventListener('change', e=> setLocked(!e.target.checked));

  async function loadCustomerOptions(q=''){
    try{
      const res = await fetch(`${OPT_URL}?q=${encodeURIComponent(q)}`, {
        headers: {'X-Requested-With':'XMLHttpRequest'}
      });
      if(!res.ok) throw new Error('options failed');
      const data = await res.json();
      [...selectBox.options].slice(1).forEach(o=>o.remove());
      data.forEach(row=> selectBox.add(new Option(row.text, row.id)));
    }catch(err){ console.error(err); }
  }

  async function fillCustomer(id){
    if(!id){ hiddenId.value=''; return; }
    try{
      const res = await fetch(`${SHOW_URL}/${id}.json`, {
        headers: {'X-Requested-With':'XMLHttpRequest'}
      });
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

  // initial
  document.addEventListener('DOMContentLoaded', async ()=>{
    await loadCustomerOptions('');
    const initial = selectBox.dataset.initial;
    if(initial){ selectBox.value = initial; await fillCustomer(initial); }
  });

  // ---------- ITEMS ----------
  const table=document.querySelector('#itemsTable tbody');
  const addBtn=document.getElementById('addRow');

  function recalc(){
    table.querySelectorAll('tr').forEach(tr=>{
      const q=parseFloat(tr.querySelector('.qty')?.value||0);
      const p=parseFloat(tr.querySelector('.price')?.value||0);
      tr.querySelector('.line-total').value=(q*p).toFixed(2);
    });
  }
  table.addEventListener('input',recalc);

  addBtn.addEventListener('click',()=>{
    const i=table.children.length;
    const tr=document.createElement('tr');
    tr.innerHTML = `
      <td><input class="form-control" name="items[${i}][description]"></td>
      <td><input type="number" step="0.01" class="form-control qty" name="items[${i}][qty]"></td>
      <td><input type="number" step="0.01" class="form-control price" name="items[${i}][unit_price]"></td>
      <td><input class="form-control line-total" value="0.00" readonly></td>
      <td><button type="button" class="btn btn-light btn-sm remove-row"><i class="bi bi-x-lg"></i></button></td>`;
    table.appendChild(tr); recalc();
  });

  table.addEventListener('click',(e)=>{
    if(e.target.closest('.remove-row')){
      e.target.closest('tr').remove(); recalc();
    }
  });

  recalc();
})();
</script>
@endpush
@endsection
