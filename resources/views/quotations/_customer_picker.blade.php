{{-- resources/views/quotations/_customer_picker.blade.php --}}
<div class="panel mb-3">
  <div class="panel-header"><strong>Customer</strong></div>
  <div class="panel-body">
    <div class="row g-3 align-items-end">
      <div class="col-md-6">
        <label for="customer_id" class="form-label">Select Customer</label>
        <select id="customer_id" name="customer_id" class="form-select" data-initial="{{ old('customer_id', $quotation->customer_id ?? '') }}">
          <option value="">— เลือกลูกค้า —</option>
        </select>
        <div class="form-text">พิมพ์ค้นชื่อ/เลขผู้เสียภาษีได้</div>
      </div>
      <div class="col-md-6 text-end">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="unlockFields">
          <label class="form-check-label" for="unlockFields">ปลดล็อกเพื่อแก้ไขรายละเอียดที่ดึงมา</label>
        </div>
      </div>
    </div>

    <hr>

    {{-- ฟิลด์ที่ต้องการให้ auto-fill (คงโครงที่หนิงใช้อยู่ได้) --}}
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Customer Name</label>
        <input id="cust_name" name="customer_name" type="text" class="form-control" value="{{ old('customer_name',$quotation->customer_name ?? '') }}">
      </div>
      <div class="col-md-6">
        <label class="form-label">Address</label>
        <textarea id="cust_address" name="customer_address" class="form-control" rows="2">{{ old('customer_address',$quotation->customer_address ?? '') }}</textarea>
      </div>

      <div class="col-md-3">
        <label class="form-label">Tax ID</label>
        <input id="cust_tax" name="customer_tax_id" type="text" class="form-control" value="{{ old('customer_tax_id',$quotation->customer_tax_id ?? '') }}">
      </div>
      <div class="col-md-3">
        <label class="form-label">Tel.</label>
        <input id="cust_tel" name="customer_tel" type="text" class="form-control" value="{{ old('customer_tel',$quotation->customer_tel ?? '') }}">
      </div>
      <div class="col-md-3">
        <label class="form-label">Fax</label>
        <input id="cust_fax" name="customer_fax" type="text" class="form-control" value="{{ old('customer_fax',$quotation->customer_fax ?? '') }}">
      </div>
      <div class="col-md-3">
        <label class="form-label">Payment Terms</label>
        <input id="cust_terms" name="payment_terms" type="text" class="form-control" value="{{ old('payment_terms',$quotation->payment_terms ?? '') }}">
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
(function(){
  const $sel   = document.getElementById('customer_id');
  const $unlock= document.getElementById('unlockFields');
  const ids = {
    name: document.getElementById('cust_name'),
    address: document.getElementById('cust_address'),
    tax: document.getElementById('cust_tax'),
    tel: document.getElementById('cust_tel'),
    fax: document.getElementById('cust_fax'),
    terms: document.getElementById('cust_terms'),
  };

  // ล็อกฟิลด์ไว้ก่อน
  const setLocked = (locked) => {
    [ids.name, ids.address, ids.tax, ids.tel, ids.fax, ids.terms].forEach(el=>{
      el.readOnly = locked && (el.tagName !== 'TEXTAREA');
      if (el.tagName === 'TEXTAREA') el.disabled = locked;
    });
  };
  setLocked(true);
  $unlock.addEventListener('change', e=> setLocked(!e.target.checked));

  // โหลด options ลูกค้า (ครั้งแรก + เวลา search)
  const loadOptions = async (q='') => {
    const res = await fetch(`{{ route('customers.options') }}?q=`+encodeURIComponent(q));
    const data = await res.json();
    // clear keep first option
    [...$sel.options].slice(1).forEach(o=>o.remove());
    data.forEach(row=>{
      const opt = new Option(row.text, row.id);
      $sel.add(opt);
    });
  };
  loadOptions('');

  // รองรับพิมพ์ค้นหาแบบง่าย (debounce)
  let timer=null;
  $sel.addEventListener('keyup', (e)=>{
    clearTimeout(timer);
    timer=setTimeout(()=>loadOptions(e.target.value||''), 300);
  });

  // เมื่อเลือกลูกค้า -> ดึงรายละเอียดมาใส่
  const fillById = async (id) => {
    if (!id) return;
    const res = await fetch(`{{ url('/api/customers') }}/${id}.json`);
    const c = await res.json();
    ids.name.value    = c.name || '';
    ids.address.value = c.address || '';
    ids.tax.value     = c.tax_id || '';
    ids.tel.value     = c.tel || '';
    ids.fax.value     = c.fax || '';
    ids.terms.value   = c.payment_terms || '';
  };

  $sel.addEventListener('change', e=> fillById(e.target.value));

  // ถ้ามีค่าเดิม (ตอนแก้ไขเอกสาร) ให้เลือกและเติมให้เลย
  const initial = $sel.dataset.initial;
  if (initial) {
    (async()=>{
      await loadOptions('');
      $sel.value = initial;
      await fillById(initial);
    })();
  }
})();
</script>
@endpush
