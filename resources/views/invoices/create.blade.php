 @extends('layouts.app')

@section('content')
<form method="post" action="{{ route('invoices.store') }}" id="invoice-form" class="grid lg:grid-cols-3 gap-6">
  @csrf

  {{-- ซ้าย: รายละเอียด + รายการ --}}
  <div class="lg:col-span-2 space-y-6">

    {{-- หัวเรื่อง --}}
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-3xl font-bold">New Invoice</h1>
        <p class="text-sm text-gray-500">สร้างใบกำกับภาษี/ใบแจ้งหนี้</p>
      </div>
      <a href="{{ route('invoices.index') }}" class="btn btn-ghost">Cancel</a>
    </div>

    {{-- รายละเอียด --}}
    <div class="card space-y-4">
      <div>
        <label class="block text-sm font-medium">Customer Name</label>
        <input name="customer_name" class="input mt-1" required>
      </div>

      <div class="grid sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-sm font-medium">Issue date</label>
          <input type="date" name="issue_date" value="{{ now()->toDateString() }}" class="input mt-1" required>
        </div>
        <div>
          <label class="block text-sm font-medium">Due date</label>
          <input type="date" name="due_date" class="input mt-1">
        </div>
        <div>
          <label class="block text-sm font-medium">Tax rate (%)</label>
          <input type="number" step="0.01" name="tax_rate" class="input mt-1" value="7">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium">Notes/Detail (optional)</label>
        <textarea name="notes" rows="2" class="input mt-1"></textarea>
      </div>
    </div>

    {{-- รายการสินค้า --}}
    <div class="card">
      <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-semibold">Items</h2>
        <button type="button" id="add-line" class="btn btn-ghost">+ Add line</button>
      </div>

      <div class="overflow-x-auto">
        <table class="table">
          <thead class="border-b bg-gray-50">
            <tr>
              <th class="th w-12">No.</th>
              <th class="th">Name / Description</th>
              <th class="th w-28">Qty</th>
              <th class="th w-24">Unit</th>
              <th class="th w-40">Unit Price</th>
              <th class="th w-40">Total</th>
              <th class="th w-12"></th>
            </tr>
          </thead>
          <tbody id="item-rows"></tbody>
        </table>
      </div>
    </div>

    {{-- ปุ่มล่างสำหรับจอเล็ก --}}
    <div class="lg:hidden flex gap-3">
      <button type="submit" class="btn btn-primary flex-1">Save</button>
      <a href="{{ route('invoices.index') }}" class="btn btn-ghost">Cancel</a>
    </div>
  </div>

  {{-- ขวา: สรุปยอด --}}
  <aside class="card right-panel h-fit space-y-4">
    <h3 class="text-lg font-semibold">Summary</h3>

    <div class="flex items-center justify-between">
      <span class="text-sm text-gray-600">Total</span>
      <span id="sum-subtotal" class="font-semibold">0.00</span>
    </div>

    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <span class="text-sm text-gray-600">Discount</span>
      </div>
      <div class="flex items-center gap-2">
        <input id="discount" type="number" step="0.01" class="input w-28 text-right" value="0">
        <span class="text-sm text-gray-600">%</span>
      </div>
    </div>

    <div class="flex items-center justify-between">
      <span class="text-sm text-gray-600">Total after Discount</span>
      <span id="sum-after-discount" class="font-semibold">0.00</span>
    </div>

    <label class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <input id="vat-enabled" type="checkbox" class="h-4 w-4 text-indigo-600 rounded" checked>
        <span class="text-sm text-gray-600">VAT <span id="vat-rate-label">7%</span></span>
      </div>
      <span id="sum-vat" class="font-semibold">0.00</span>
    </label>

    <hr>

    <div class="flex items-center justify-between">
      <span class="text-base font-semibold">Grand Total</span>
      <span id="sum-grand" class="text-xl font-bold">0.00</span>
    </div>

    {{-- hidden fields ส่งไป backend --}}
    <input type="hidden" name="subtotal" id="field-subtotal">
    <input type="hidden" name="discount_percent" id="field-discount">
    <input type="hidden" name="tax" id="field-tax">
    <input type="hidden" name="total" id="field-total">

    <button type="submit" class="btn btn-primary w-full">Save</button>
  </aside>
</form>

@if($errors->any())
  <div class="card mt-6 border border-rose-200 bg-rose-50">
    <strong class="text-rose-700">มีข้อผิดพลาด:</strong>
    <ul class="list-disc ml-5 mt-2 text-rose-700">
      @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
    </ul>
  </div>
@endif

{{-- Template แถวรายการ (JS จะ clone) --}}
<template id="row-template">
  <tr class="border-b last:border-0">
    <td class="td text-gray-500 seq"></td>
    <td class="td">
      <input name="" class="input item-desc" required>
    </td>
    <td class="td">
      <input type="number" min="1" step="1" value="1" class="input text-right item-qty" required>
    </td>
    <td class="td">
      <input class="input item-unit" placeholder="-">
    </td>
    <td class="td">
      <input type="number" step="0.01" min="0" value="0" class="input text-right item-price" required>
    </td>
    <td class="td">
      <input type="text" class="input text-right bg-gray-50 item-line" value="0.00" readonly>
    </td>
    <td class="td text-right">
      <button type="button" class="btn btn-danger btn-sm remove-row">✕</button>
    </td>
  </tr>
</template>

<script>
(function(){
  const tbody = document.getElementById('item-rows');
  const tpl   = document.getElementById('row-template');
  const addBtn= document.getElementById('add-line');

  const discountEl = document.getElementById('discount');
  const vatChk     = document.getElementById('vat-enabled');
  const vatRateLbl = document.getElementById('vat-rate-label');

  const taxRateInput = document.querySelector('input[name="tax_rate"]');

  const sumSubtotalEl = document.getElementById('sum-subtotal');
  const sumAfterDiscEl= document.getElementById('sum-after-discount');
  const sumVatEl      = document.getElementById('sum-vat');
  const sumGrandEl    = document.getElementById('sum-grand');

  const fSubtotal = document.getElementById('field-subtotal');
  const fDiscount = document.getElementById('field-discount');
  const fTax      = document.getElementById('field-tax');
  const fTotal    = document.getElementById('field-total');

  function addRow(desc='', qty=1, unit='', price=0){
    const clone = tpl.content.cloneNode(true);
    const row   = clone.querySelector('tr');
    const idx   = tbody.children.length;

    const descEl  = row.querySelector('.item-desc');
    const qtyEl   = row.querySelector('.item-qty');
    const unitEl  = row.querySelector('.item-unit');
    const priceEl = row.querySelector('.item-price');

    // name attributes สำหรับส่งไป backend
    descEl.setAttribute('name',  `items[${idx}][description]`);
    qtyEl.setAttribute('name',   `items[${idx}][qty]`);
    unitEl.setAttribute('name',  `items[${idx}][unit]`);
    priceEl.setAttribute('name', `items[${idx}][price]`);

    // hidden line_total (เก็บตอน submit)
    const hiddenLine = document.createElement('input');
    hiddenLine.type = 'hidden';
    hiddenLine.name = `items[${idx}][line_total]`;
    hiddenLine.className = 'hidden-line';
    row.appendChild(hiddenLine);

    row.querySelector('.seq').textContent = idx+1;
    descEl.value  = desc;
    qtyEl.value   = qty;
    unitEl.value  = unit;
    priceEl.value = price;

    row.addEventListener('input', recalc);
    row.querySelector('.remove-row').addEventListener('click', ()=>{
      row.remove(); resequence(); recalc();
    });

    tbody.appendChild(row);
    recalc();
  }

  function resequence(){
    [...tbody.children].forEach((tr,i)=>{
      tr.querySelector('.seq').textContent = i+1;
      tr.querySelector('.item-desc').setAttribute('name', `items[${i}][description]`);
      tr.querySelector('.item-qty').setAttribute('name',  `items[${i}][qty]`);
      tr.querySelector('.item-unit').setAttribute('name', `items[${i}][unit]`);
      tr.querySelector('.item-price').setAttribute('name',`items[${i}][price]`);
      tr.querySelector('.hidden-line').setAttribute('name',`items[${i}][line_total]`);
    });
  }

  function recalc(){
    // ถ้ายกเลิก VAT -> ตั้ง tax_rate เป็น 0 อัตโนมัติ
    if (!vatChk.checked) {
      taxRateInput.value = 0;
    }
    // อัปเดต label อัตรา VAT
    vatRateLbl.textContent = (Number(taxRateInput.value || 0)).toFixed(0) + '%';

    let subtotal = 0;
    [...tbody.children].forEach(tr=>{
      const qty   = Number(tr.querySelector('.item-qty').value || 0);
      const price = Number(tr.querySelector('.item-price').value || 0);
      const line  = qty * price;
      tr.querySelector('.item-line').value   = line.toFixed(2);
      tr.querySelector('.hidden-line').value = line.toFixed(2);
      subtotal += line;
    });

    const discP = Number(discountEl.value || 0); // %
    const afterDisc = subtotal * (1 - discP/100);

    const vatRate = Number(taxRateInput.value || 0)/100;
    const vat = vatChk.checked ? afterDisc * vatRate : 0;

    const grand = afterDisc + vat;

    // แสดงผล
    sumSubtotalEl.textContent  = subtotal.toFixed(2);
    sumAfterDiscEl.textContent = afterDisc.toFixed(2);
    sumVatEl.textContent       = vat.toFixed(2);
    sumGrandEl.textContent     = grand.toFixed(2);

    // hidden fields ส่งไป backend
    fSubtotal.value = subtotal.toFixed(2);
    fDiscount.value = discP.toFixed(2);
    fTax.value      = vat.toFixed(2);
    fTotal.value    = grand.toFixed(2);
  }

  addBtn.addEventListener('click', ()=> addRow());
  discountEl.addEventListener('input', recalc);
  vatChk.addEventListener('change', recalc);
  taxRateInput.addEventListener('input', recalc);

  // กันเคสไม่มีรายการ + คำนวณล่าสุดก่อน submit
  document.getElementById('invoice-form').addEventListener('submit', (e)=>{
    if (tbody.children.length === 0) {
      e.preventDefault();
      alert('กรุณาเพิ่มรายการสินค้าอย่างน้อย 1 รายการ');
      return;
    }
    recalc(); // อัปเดต hidden fields ให้ตรงค่าล่าสุด
  });

  // แถวแรก
  addRow('', 1, '', 0);
})();
</script>
@endsection
