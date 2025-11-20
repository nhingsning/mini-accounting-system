@extends('layouts.app')

@section('body')


<style>
:root{--brand:#31689E;--ink:#0f172a;--muted:#6b7280;--line:#e5e7eb;--bg:#f8fafc;--card:#ffffff}
body{background:var(--bg);color:var(--ink)}
.fa-wrap{max-width:1220px;margin:0 auto;padding:18px 18px 24px}
.fa-topbar{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;gap:16px}
.fa-title{font-size:22px;font-weight:800;color:var(--ink);letter-spacing:-0.01em;margin-bottom:6px}
.fa-subtitle{color:var(--muted);font-size:13px;margin-bottom:6px}
.fa-badge{display:inline-flex;align-items:center;gap:8px;background:#e9f2fb;color:var(--brand);border:1px solid #c5dbf1;padding:4px 12px;border-radius:999px;font-weight:700;font-size:12px}
.fa-number{font-size:18px;font-weight:800;color:var(--brand);letter-spacing:0.04em}
.fa-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.fa-btn{display:inline-flex;align-items:center;gap:6px;border-radius:10px;border:1px solid var(--line);padding:10px 14px;text-decoration:none;font-weight:700;box-shadow:0 8px 20px -15px rgba(15,23,42,0.2);transition:all .15s ease;background:#fff;color:var(--ink)}
.fa-btn.save{background:var(--brand);color:#fff;border-color:var(--brand);box-shadow:0 10px 25px -18px rgba(49,104,158,0.9)}
.fa-btn.light{background:#fff;color:var(--ink)}
.fa-btn.ghost{background:#f1f5f9;color:var(--ink);border-color:#d9e3ef}
.fa-btn:hover{transform:translateY(-1px);box-shadow:0 12px 28px -20px rgba(15,23,42,0.35)}
.fa-card{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:0 16px 50px -35px rgba(15,23,42,0.35)}
.fa-grid{display:grid;grid-template-columns:1fr 320px;gap:18px}
@media (max-width: 1024px){.fa-grid{grid-template-columns:1fr}}
.fa-section{padding:18px 18px 20px}
.fa-section .section-title{font-weight:700;color:var(--ink);font-size:15px;margin-bottom:10px;display:flex;align-items:center;gap:8px}
.fa-meta dl{display:grid;grid-template-columns:130px 1fr;gap:10px 12px;margin:0}
.fa-meta dt{color:var(--muted);font-weight:600} .fa-meta dd{margin:0;font-weight:700}
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
.fa-table .no{width:64px;text-align:center;color:var(--muted)}
.fa-table .qty,.fa-table .price,.fa-table .line{text-align:right;width:140px}
.fa-sticky{position:sticky;top:12px;display:flex;flex-direction:column;gap:12px}
.fa-totals .row{display:flex;justify-content:space-between;margin:7px 0;font-size:14px}
.fa-totals .row strong{font-weight:800;color:var(--ink)}
.fa-add{margin-top:10px}
.fa-del{background:#fff;border:1px solid var(--line);border-radius:10px;padding:6px 12px;color:#ef4444;box-shadow:0 6px 16px -14px rgba(15,23,42,0.5)}
.fa-copy{background:#fff;border:1px solid var(--line);border-radius:10px;padding:6px 12px;color:var(--brand);box-shadow:0 6px 16px -14px rgba(15,23,42,0.5);margin-right:6px}
.text-right{text-align:right}
.fa-badge{display:inline-block;background:#eef2ff;color:var(--brand);border:1px solid var(--brand);padding:2px 10px;border-radius:999px;font-size:12px;font-weight:700}
.stack{display:flex;flex-direction:column;gap:8px}
.alert{border-radius:12px;padding:10px 12px;border:1px solid;margin-bottom:12px}
.alert-danger{background:#fff1f2;border-color:#fecdd3;color:#991b1b}
.alert-success{background:#ecfdf5;border-color:#a7f3d0;color:#065f46}
.helper{color:var(--muted);font-size:12px;margin-top:4px}
</style>


@php
  $quotation = $quotation ?? null; // กัน null เวลาใช้ร่วมกับหน้า create
  $isEdit   = optional($quotation)->exists;
  $formAction = $isEdit ? route('quotations.update', $quotation) : route('quotations.store');
  $number   = $quotation->number ?? $nextNumber ?? $provisionalNumber ?? 'QT'.now()->format('Ymd').'-????';
  $status   = old('status', optional($quotation)->status ?? 'draft');
  $taxRate  = old('tax_rate', optional($quotation)->tax_rate ?? 0);
  $existingItems = collect(optional($quotation)->items ?? [])->map(function($it){
    return [
      'id'          => $it->id,
      'description' => $it->description,
      'quantity'    => $it->quantity ?? $it->qty ?? 1,
      'unit_price'  => $it->unit_price ?? $it->price ?? 0,
      'discount'    => $it->discount ?? 0,
    ];
  })->toArray();
  $rows     = old('items', $existingItems ?: [['id'=>null,'description'=>'','quantity'=>1,'unit_price'=>0]]);
@endphp

<div class="fa-wrap">
  <div class="fa-topbar">
    <div>
      <div class="fa-subtitle">Quotation</div>
      <div class="fa-title">{{ $isEdit ? 'แก้ไขใบเสนอราคา' : 'สร้างใบเสนอราคา' }}</div>
      <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <span class="fa-number">{{ $number }}</span>
        <span class="fa-badge">{{ strtoupper($status) }}</span>
      </div>
    </div>
    <div class="fa-actions">
      <a href="{{ route('quotations.index') }}" class="fa-btn ghost">ย้อนกลับ</a>
      <button type="button" class="fa-btn light" id="previewBtn">พรีวิว</button>
      <button type="button" class="fa-btn light" data-status="draft" id="draftBtn">{{ $isEdit ? 'บันทึกเป็นร่าง' : 'บันทึกแบบร่าง' }}</button>
      <button type="button" class="fa-btn save" data-status="sent" id="finalBtn">{{ $isEdit ? 'บันทึกการแก้ไข' : 'บันทึก &amp; ส่ง' }}</button>
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

  <form id="qForm" method="POST" action="{{ $formAction }}" autocomplete="off" enctype="multipart/form-data">
    @csrf
    @if($isEdit)
      @method('PUT')
    @endif
    <input type="hidden" name="status" id="statusInput" value="{{ $status }}">
    <input type="hidden" name="customer_id" id="customer_id_hidden" value="{{ old('customer_id', optional($quotation)->customer_id) }}">

    <div class="fa-grid">
      {{-- LEFT --}}
      <div class="fa-card fa-section">
        <div class="section-title">Customer &amp; Document Details</div>
        <div style="display:grid;grid-template-columns:1fr;gap:12px">

          {{-- ===== Customer Picker (ใหม่) ===== --}}
          <div class="fa-two" style="align-items:end">
            <div class="span-2">
              <label class="fa-label">Select Customer</label>
              <select class="fa-select" id="customer_id_select" data-initial="{{ old('customer_id', optional($quotation)->customer_id) }}">
                <option value="">— เลือกลูกค้า —</option>
              </select>
              <div class="helper">เลือกจากรายชื่อลูกค้าที่มีและระบบจะเติมข้อมูลอัตโนมัติ</div>
            </div>
            <div class="span-2" style="display:flex;justify-content:flex-end;gap:10px">
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input type="checkbox" id="unlockFields">
                <span class="fa-label" style="margin:0">ปลดล็อกเพื่อแก้ไขรายละเอียดลูกค้าในเอกสารนี้</span>
              </label>
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
              <label class="fa-label">Contact Person</label>
              <input id="contact_name" type="text" name="contact_name" class="fa-input"
                     value="{{ old('contact_name', optional($quotation)->contact_name) }}"
                     placeholder="ผู้ติดต่อหลัก">
            </div>

            <div>
              <label class="fa-label">Contact Email</label>
              <input id="contact_email" type="email" name="contact_email" class="fa-input"
                     value="{{ old('contact_email', optional($quotation)->contact_email) }}"
                     placeholder="อีเมลสำหรับติดต่อ">
            </div>

            <div>
              <label class="fa-label">Contact Phone</label>
              <input id="contact_phone" type="text" name="contact_phone" class="fa-input"
                     value="{{ old('contact_phone', optional($quotation)->contact_phone) }}"
                     placeholder="เบอร์โทร">
            </div>

            <div>
              <label class="fa-label">Salesperson</label>
              <input type="text" name="salesperson" class="fa-input"
                     value="{{ old('salesperson', optional($quotation)->salesperson) }}"
                     placeholder="พนักงานขาย">
            </div>

            <div>
              <label class="fa-label">Payment Terms</label>
              <div class="stack">
                <select class="fa-select" id="payment_terms_templates">
                  <option value="">— เลือกจาก Template —</option>
                  <option value="จ่ายภายใน 7 วัน">จ่ายภายใน 7 วัน</option>
                  <option value="เครดิต 30 วัน">เครดิต 30 วัน</option>
                  <option value="เครดิต 45 วัน">เครดิต 45 วัน</option>
                </select>
                <input type="text" name="payment_terms" id="payment_terms" class="fa-input"
                       value="{{ old('payment_terms', optional($quotation)->payment_terms) }}"
                       placeholder="เช่น 30 วัน, เครดิต 45 วัน">
              </div>
            </div>

            <div>
              <label class="fa-label">Discount (%)</label>
              <input type="number" min="0" step="0.01" name="discount_percent" class="fa-input"
                     value="{{ old('discount_percent', optional($quotation)->discount_percent ?? 0) }}"
                     oninput="recalcTotals()">
            </div>

            <div>
              <label class="fa-label">Discount (Amount)</label>
              <input type="number" min="0" step="0.01" name="discount_amount" class="fa-input"
                     value="{{ old('discount_amount', optional($quotation)->discount_amount ?? 0) }}"
                     oninput="recalcTotals()">
            </div>

            <div class="span-2">
              <label class="fa-label">Detail / Notes</label>
              <div class="fa-two">
                <div>
                  <select class="fa-select" id="terms_templates">
                    <option value="">— เลือก Terms &amp; Conditions —</option>
                    <option value="ชำระ 30 วันหลังรับสินค้า">ชำระ 30 วันหลังรับสินค้า</option>
                    <option value="รับประกันสินค้า 90 วัน">รับประกันสินค้า 90 วัน</option>
                    <option value="ราคานี้ไม่รวมค่าขนส่งและติดตั้ง">ราคานี้ไม่รวมค่าขนส่งและติดตั้ง</option>
                  </select>
                </div>
                <div>
                  <select class="fa-select" id="warranty_templates">
                    <option value="">— ระยะเวลารับประกัน —</option>
                    <option value="รับประกัน 30 วัน">รับประกัน 30 วัน</option>
                    <option value="รับประกัน 90 วัน">รับประกัน 90 วัน</option>
                    <option value="รับประกัน 1 ปี">รับประกัน 1 ปี</option>
                  </select>
                </div>
              </div>
              <textarea class="fa-textarea" name="notes" id="notes_box"
                        placeholder="ระบุเงื่อนไขการขาย / การชำระเงิน / หมายเหตุ">{{ old('notes', optional($quotation)->notes) }}</textarea>
              <div class="helper">เลือก template ด้านบนเพื่อแทรกข้อความลงในช่องนี้ทันที</div>
            </div>
          </div>
        </div>

        <div class="section-title" style="margin-top:10px">Line Items</div>
        <div style="margin-top:10px">
      <table class="fa-table" id="itemsTable">
        <thead>
          <tr>
            <th class="no">No.</th>
            <th>Name / Description</th>
                <th class="qty">Quantity</th>
                <th class="price">Unit Price</th>
                <th class="price">Discount</th>
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
                $disc = (float)($item['discount'] ?? 0);
                $line = ($qty * $unit) - $disc;
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
                <td class="price">
                  <input class="fa-input discount" type="number" min="0" step="0.01" name="items[{{ $i }}][discount]" value="{{ $disc ?? 0 }}" oninput="recalcTotals()">
                </td>
                <td class="line line-total">{{ number_format($line,2) }}</td>
                <td class="text-right">
                  <button type="button" class="fa-copy" onclick="copyRow(this)">Copy</button>
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

        <div class="fa-section" style="margin-top:14px">
          <div class="section-title">Attachments</div>
          <input type="file" name="attachments[]" id="attachments" class="fa-input" multiple
                 accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
          <div class="helper">แนบไฟล์ PDF / รูปภาพ / เอกสาร Word เพื่อเก็บพร้อมใบเสนอราคา</div>
        </div>
      </div>

      {{-- RIGHT --}}
      <div class="fa-sticky">
        <div class="fa-card fa-section fa-meta" style="margin-bottom:12px">
          <div class="section-title">Document Info</div>
          <dl>
            <dt>Quotation No.</dt>
            <dd>
              <input class="fa-input" type="text" name="number"
                     value="{{ old('number', $number) }}" placeholder="เว้นว่างให้ระบบออกเลขอัตโนมัติ">
              <div class="helper">ปรับเลขได้เอง หรือเว้นว่างให้ระบบรัน QTYYYY-MM-#### ให้อัตโนมัติ</div>
            </dd>
            <dt>Status</dt><dd><span class="fa-badge" id="statusBadge">{{ ucfirst(old('status','draft')) }}</span></dd>

            <dt>Date</dt>
            <dd><input class="fa-input" type="date" name="issue_date"
                       value="{{ old('issue_date', now()->format('Y-m-d')) }}"></dd>

            <dt>Valid Until</dt>
            <dd><input class="fa-input" type="date" name="valid_until"
                       value="{{ old('valid_until', optional($quotation)->valid_until) }}"></dd>

            <dt>VAT Mode</dt>
            <dd>
              @php $mode = old('vat_mode', optional($quotation)->vat_mode ?? 'exclusive'); @endphp
              <div style="display:flex;flex-direction:column;gap:6px">
                <label style="display:flex;align-items:center;gap:8px">
                  <input type="radio" name="vat_mode" value="exclusive" {{ $mode==='exclusive'?'checked':'' }} onchange="recalcTotals()">
                  <span class="fa-label" style="margin:0">Exclude VAT (add on top)</span>
                </label>
                <label style="display:flex;align-items:center;gap:8px">
                  <input type="radio" name="vat_mode" value="inclusive" {{ $mode==='inclusive'?'checked':'' }} onchange="recalcTotals()">
                  <span class="fa-label" style="margin:0">Include VAT (prices already include)</span>
                </label>
                <label style="display:flex;align-items:center;gap:8px">
                  <input type="radio" name="vat_mode" value="none" {{ $mode==='none'?'checked':'' }} onchange="recalcTotals()">
                  <span class="fa-label" style="margin:0">No VAT</span>
                </label>
              </div>
            </dd>

            <dt>Tax Rate (%)</dt>
            <dd>
              @php $rate = (float)old('tax_rate', optional($quotation)->tax_rate ?? $taxRate); @endphp
              <select class="fa-select" name="tax_rate" onchange="recalcTotals()">
                <option value="0" {{ $rate==0 ? 'selected' : '' }}>0%</option>
                <option value="3" {{ $rate==3 ? 'selected' : '' }}>3%</option>
                <option value="7" {{ $rate==7 ? 'selected' : '' }}>7%</option>
              </select>
              <label style="display:flex;align-items:center;gap:8px;margin-top:8px">
                @php $vatOn = old('vat_enabled', optional($quotation)->vat_enabled ?? true) ? true : false; @endphp
                <input type="checkbox" name="vat_enabled" value="1"
                       {{ $vatOn ? 'checked' : '' }} onchange="recalcTotals()">
                <span class="fa-label" style="margin:0">Apply VAT</span>
              </label>
              <div style="margin-top:10px">
                <label class="fa-label">Withholding Tax</label>
                @php $wht = (float)old('withholding_rate', optional($quotation)->withholding_rate ?? 0); @endphp
                <select class="fa-select" name="withholding_rate" onchange="recalcTotals()">
                  <option value="0" {{ $wht==0?'selected':'' }}>ไม่มีหัก ณ ที่จ่าย</option>
                  <option value="1" {{ $wht==1?'selected':'' }}>1%</option>
                  <option value="3" {{ $wht==3?'selected':'' }}>3%</option>
                  <option value="5" {{ $wht==5?'selected':'' }}>5%</option>
                </select>
              </div>
            </dd>
          </dl>
        </div>

    <div class="fa-card fa-section fa-totals">
      <div class="section-title" style="margin-bottom:4px">Summary</div>
      <div class="row"><span>Subtotal</span><strong id="subTotal">0.00</strong></div>
      <div class="row"><span>Discount</span><strong id="discTotal">0.00</strong></div>
      <div class="row"><span>Tax</span><strong id="taxTotal">0.00</strong></div>
      <div class="row"><span>Withholding (WHT)</span><strong id="whtTotal">0.00</strong></div>
      <div class="row" style="border-top:1px dashed var(--line);padding-top:8px">
        <span>Total</span><strong id="grandTotal">0.00</strong>
      </div>
    </div>

      </div>
    </div>

    {{-- hidden totals for validator --}}
    <input type="hidden" name="subtotal"         id="subtotalInput"  value="0">
    <input type="hidden" name="discount_total"   id="discountInput"  value="0">
    <input type="hidden" name="tax"              id="taxInput"       value="0">
    <input type="hidden" name="total"            id="totalInput"     value="0">

  </form>

  <div id="previewModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.52);z-index:90;align-items:center;justify-content:center;padding:18px;">
    <div style="background:#fff;border-radius:16px;max-width:960px;width:100%;max-height:92vh;overflow:auto;box-shadow:0 20px 50px rgba(0,0,0,0.35);padding:18px 20px;">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px;">
        <div style="font-weight:700;font-size:18px;">Preview</div>
        <button type="button" class="fa-del" onclick="closePreview()">Close</button>
      </div>
      <div id="previewContent" style="font-size:14px;color:#0f172a"></div>
    </div>
  </div>
</div>

<script>
(function () {
  // ---------- URLs (ชัวร์สุด ไม่พึ่งชื่อ route) ----------
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
    contactName: document.getElementById('contact_name'),
    contactEmail: document.getElementById('contact_email'),
    contactPhone: document.getElementById('contact_phone'),
    paymentTerms: document.getElementById('payment_terms'),
  };

  function setLocked(locked){
    // ใช้ readOnly ทั้งหมด เพื่อให้ submit ได้
    [fields.name, fields.tax, fields.branchCode].forEach(el=>{ if(el) el.readOnly = locked; });
    if(fields.address) fields.address.readOnly = locked;
    // branchType เป็น select: ไม่ปิด disable เพื่อให้ส่งค่าได้
  }
  setLocked(true);
  unlockBox?.addEventListener('change', e=> setLocked(!e.target.checked));

  async function loadCustomerOptions(q=''){
    try {
      const res = await fetch(`${OPT_URL}?q=${encodeURIComponent(q)}`, {
        headers: {'X-Requested-With':'XMLHttpRequest'}
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
        headers: {'X-Requested-With':'XMLHttpRequest'}
      });
      if(!res.ok) throw new Error('Customer not found');
      const payload = await res.json();
      const c = payload.customer || {};
      const contact = payload.contact || {};
      hiddenId.value = c.id || '';
      if(fields.name)        fields.name.value        = c.name || '';
      if(fields.address)     fields.address.value     = c.address_show || c.address || '';
      if(fields.tax)         fields.tax.value         = c.tax_id || '';
      if(fields.branchType)  fields.branchType.value  = (c.office_type || (c.is_branch ? 'branch' : 'head') || '-');
      if(fields.branchCode)  fields.branchCode.value  = c.branch_code || '';
      if(fields.contactName) fields.contactName.value = contact.contact_name || c.contact_name || '';
      if(fields.contactEmail)fields.contactEmail.value= contact.email || c.contact_email || '';
      if(fields.contactPhone)fields.contactPhone.value= contact.mobile || c.contact_phone || '';
      if(fields.paymentTerms)fields.paymentTerms.value= c.payment_terms || '';
    } catch(err){
      console.error(err);
    }
  }

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

  window.copyRow = function(btn){
    const tr = btn.closest('tr');
    if(!tr) return;
    const clone = tr.cloneNode(true);
    tr.parentNode.insertBefore(clone, tr.nextSibling);
    renumberRows();
    recalcTotals();
  };

  // คำนวณยอด + sync ไปที่กล่องด้านขวา + hidden
  window.recalcTotals = function(){
    const tbody=document.querySelector('#itemsTable tbody'); let sub=0;
    if(tbody){
      tbody.querySelectorAll('tr').forEach(tr=>{
        const q=num(tr.querySelector('input.qty'));
        const p=num(tr.querySelector('input.price'));
        const d=num(tr.querySelector('input.discount'));
        const line=q*p - d;
        const cell=tr.querySelector('.line-total'); if(cell) cell.textContent=fmt(line);
        sub+=line;
      });
    }

    const discPct = num(document.querySelector('[name="discount_percent"]'));
    const discAmt = num(document.querySelector('[name="discount_amount"]'));
    const discTotal = (sub * (discPct/100)) + discAmt;
    const afterDisc = Math.max(sub - discTotal, 0);

    const vatOn = document.querySelector('[name="vat_enabled"]')?.checked;
    const rate  = Number(document.querySelector('[name="tax_rate"]')?.value || 0);
    const mode  = document.querySelector('[name="vat_mode"]:checked')?.value || 'exclusive';

    let tax=0, total=afterDisc;
    if(vatOn && rate>0 && mode !== 'none'){
      if(mode === 'inclusive'){
        const net = afterDisc / (1 + rate/100);
        tax = afterDisc - net;
        total = afterDisc;
        sub = net; // show net as subtotal
      } else {
        tax = afterDisc * (rate/100);
        total = afterDisc + tax;
      }
    }

    const whtRate = Number(document.querySelector('[name="withholding_rate"]')?.value || 0);
    const wht = whtRate > 0 ? afterDisc * (whtRate/100) : 0;
    const finalTotal = Math.max(total - wht, 0);

    document.getElementById('subTotal').textContent  = fmt(sub);
    document.getElementById('discTotal').textContent = fmt(discTotal);
    document.getElementById('taxTotal').textContent  = fmt(tax);
    document.getElementById('whtTotal').textContent  = fmt(wht);
    document.getElementById('grandTotal').textContent= fmt(finalTotal);

    document.getElementById('subtotalInput').value = fmt(sub);
    document.getElementById('discountInput').value = fmt(discTotal);
    document.getElementById('taxInput').value      = fmt(tax);
    document.getElementById('totalInput').value    = fmt(finalTotal);
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
      tr.setAttribute('draggable','true');
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
      <td class="price"><input class="fa-input discount" type="number" min="0" step="0.01" name="items[${i}][discount]" value="0" oninput="recalcTotals()"></td>
      <td class="line line-total">0.00</td>
      <td class="text-right"><button type="button" class="fa-copy" onclick="copyRow(this)">Copy</button><button type="button" class="fa-del" onclick="this.closest('tr').remove(); renumberRows(); recalcTotals();">×</button></td>`;
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
      document.querySelectorAll('[name="vat_mode"]').forEach(r=> r.addEventListener('change', recalcTotals));

      document.querySelectorAll('button[data-status]').forEach(btn => {
        btn.addEventListener('click', (e)=>{
          const status = e.currentTarget.dataset.status || 'draft';
          const statusInput = document.getElementById('statusInput');
          if(statusInput) statusInput.value = status;
          const badge = document.getElementById('statusBadge');
          if(badge) badge.textContent = status.charAt(0).toUpperCase()+status.slice(1);
          recalcTotals();
          document.getElementById('qForm')?.submit();
        });
      });

      // Enter to add row
      document.querySelector('#itemsTable tbody')?.addEventListener('keydown', (e)=>{
        if(e.key === 'Enter' && !e.shiftKey){
          e.preventDefault();
          addItemRow();
          const lastRow = document.querySelector('#itemsTable tbody tr:last-child');
          lastRow?.querySelector('.name-text')?.focus();
        }
      });

      // drag & drop reorder
      let dragSrc = null;
      const tbody = document.querySelector('#itemsTable tbody');
      tbody?.addEventListener('dragstart', (e)=>{ dragSrc = e.target.closest('tr'); });
      tbody?.addEventListener('dragover', (e)=>{ e.preventDefault(); });
      tbody?.addEventListener('drop', (e)=>{
        e.preventDefault();
        const target = e.target.closest('tr');
        if(dragSrc && target && dragSrc !== target){
          const rows = [...tbody.querySelectorAll('tr')];
          const srcIndex = rows.indexOf(dragSrc);
          const tgtIndex = rows.indexOf(target);
          if(srcIndex < tgtIndex){
            target.after(dragSrc);
          } else {
            target.before(dragSrc);
          }
          renumberRows();
        }
      });

      // template helpers
      document.getElementById('payment_terms_templates')?.addEventListener('change', (e)=>{
        if(e.target.value){ document.getElementById('payment_terms').value = e.target.value; }
      });
      const noteBox = document.getElementById('notes_box');
      function appendNote(text){
        if(!text) return;
        if(!noteBox.value.includes(text)){
          noteBox.value = (noteBox.value ? noteBox.value + '\n' : '') + text;
        }
      }
      document.getElementById('terms_templates')?.addEventListener('change', e=> appendNote(e.target.value));
      document.getElementById('warranty_templates')?.addEventListener('change', e=> appendNote(e.target.value));

      // preview modal
      document.getElementById('previewBtn')?.addEventListener('click', ()=>{
        recalcTotals();
        const content = document.getElementById('previewContent');
        const rows = [...document.querySelectorAll('#itemsTable tbody tr')].map(tr=>{
          return {
            name: tr.querySelector('.name-text')?.value || '',
            desc: tr.querySelector('.desc-text')?.value || '',
            qty: num(tr.querySelector('.qty')),
            price: num(tr.querySelector('.price')),
            discount: num(tr.querySelector('.discount')),
            line: num(tr.querySelector('.line-total')),
          }
        });
        const note = noteBox?.value || '';
        const cust = document.getElementById('cust_name')?.value || '';
        const pay = document.getElementById('payment_terms')?.value || '';
        const total = document.getElementById('grandTotal')?.textContent || '0.00';
        content.innerHTML = `
          <div style="display:flex;justify-content:space-between;gap:12px;margin-bottom:10px;">
            <div><div class="fa-label">Customer</div><div style="font-weight:700;">${cust||'-'}</div></div>
            <div><div class="fa-label">Payment</div><div style="font-weight:700;">${pay||'-'}</div></div>
            <div><div class="fa-label">Total</div><div style="font-weight:800;font-size:18px;">${total}</div></div>
          </div>
          <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead><tr style="background:#f1f5f9"><th style="text-align:left;padding:6px">Item</th><th style="text-align:right;padding:6px">Qty</th><th style="text-align:right;padding:6px">Price</th><th style="text-align:right;padding:6px">Discount</th><th style="text-align:right;padding:6px">Line</th></tr></thead>
            <tbody>
              ${rows.map(r=>`<tr><td style="padding:6px 8px;"><div style="font-weight:700;">${r.name||'-'}</div>${r.desc?`<div style="color:#6b7280;">${r.desc}</div>`:''}</td><td style="text-align:right;padding:6px;">${r.qty.toFixed(2)}</td><td style="text-align:right;padding:6px;">${r.price.toFixed(2)}</td><td style="text-align:right;padding:6px;">${r.discount.toFixed(2)}</td><td style="text-align:right;padding:6px;">${r.line.toFixed(2)}</td></tr>`).join('')}
            </tbody>
          </table>
          ${note ? `<div style="margin-top:12px"><div class="fa-label">Notes</div><div>${note.replace(/\n/g,'<br>')}</div></div>` : ''}
        `;
        document.getElementById('previewModal').style.display='flex';
      });
      window.closePreview = function(){ document.getElementById('previewModal').style.display='none'; };

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
