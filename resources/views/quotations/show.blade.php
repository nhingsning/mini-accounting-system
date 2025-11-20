@extends('layouts.app')

@section('title','Quotation '.($quotation->number ?? ('#'.$quotation->id)))

@section('content')
<style>
:root{--brand:#2B4A72;--ink:#0f172a;--muted:#64748b;--line:#e5e7eb;--bg:#f8fafc;--card:#ffffff}
body{background:var(--bg)}
.fa-wrap{max-width:1160px;margin:0 auto;padding:20px}
.fa-topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.fa-title{font-size:20px;font-weight:700;color:var(--ink)}
.fa-actions{display:flex;gap:8px}
.fa-btn{display:inline-flex;align-items:center;gap:6px;border-radius:10px;border:1px solid var(--line);padding:8px 12px;text-decoration:none;font-weight:600}
.fa-btn.primary{background:var(--brand);color:#fff;border-color:var(--brand)}
.fa-btn.light{background:#fff;color:var(--ink)}
.fa-card{background:var(--card);border:1px solid var(--line);border-radius:14px}
.fa-grid{display:grid;grid-template-columns:1fr 340px;gap:16px}
@media (max-width: 992px){.fa-grid{grid-template-columns:1fr}}
.fa-section{padding:16px}
.fa-label{display:block;font-size:12px;color:var(--muted);margin-bottom:6px}
.fa-val{font-weight:700;color:var(--ink)}
.fa-table{width:100%;border-collapse:separate;border-spacing:0 0}
.fa-table thead th{background:var(--brand);color:#fff;border:0;padding:10px 12px;font-weight:700}
.fa-table tbody td{background:#fff;border-bottom:1px solid var(--line);padding:10px 12px;vertical-align:top}
.fa-table .no{width:64px;text-align:center}
.fa-table .qty,.fa-table .price,.fa-table .disc,.fa-table .line{text-align:right;width:140px}
.fa-name{font-weight:700;color:var(--ink)}
.fa-desc{color:var(--muted);font-size:13px;margin-top:2px;white-space:pre-wrap}
.fa-sticky{position:sticky;top:16px}
.fa-totals .row{display:flex;justify-content:space-between;margin:6px 0}
.fa-totals .row strong{font-weight:800}
.fa-badge{display:inline-block;background:#eef2ff;color:var(--brand);border:1px solid var(--brand);padding:2px 8px;border-radius:999px;font-size:12px}
.hr-dash{border-top:1px dashed var(--line);margin:8px 0 0}
</style>

@php
  $cur = config('currency.symbol','฿');

  // รองรับทั้ง relation และ array
  $items = collect($quotation->items ?? []);

  // แยกชื่อ/รายละเอียด (บรรทัดแรก = ชื่อ)
  $split = function($text){
    $text = (string)($text ?? '');
    $pos = strpos($text, "\n");
    return $pos===false ? [trim($text), '']
                        : [trim(substr($text,0,$pos)), trim(substr($text,$pos+1))];
  };

  // มี discount ไหม (เพื่อแสดงคอลัมน์)
  $hasDiscount = $items->contains(function($it){
    $d = data_get($it,'discount',0);
    return (float)$d > 0;
  });

  // คำนวณสด (เผื่อไม่มีใน DB)
  $calcSub = 0.0;
  foreach($items as $it){
    $qty  = (float) (data_get($it,'quantity', data_get($it,'qty', 0)));
    $unit = (float) (data_get($it,'unit_price', data_get($it,'price', 0)));
    $disc = (float) (data_get($it,'discount', 0));
    $calcSub += max(($qty * $unit) - $disc, 0);
  }

  $taxRate = (float) ($quotation->tax_rate ?? 0); // เป็น %
  $calcTax = $calcSub * ($taxRate/100);
  $calcTot = $calcSub + $calcTax;

  // ใช้ค่าจาก DB ก่อน ถ้าไม่มีค่อย fallback เป็นคำนวณสด
  $sub = (float) ($quotation->subtotal ?? $calcSub);
  $tax = (float) ($quotation->tax_amount ?? $quotation->tax ?? $calcTax);
  $tot = (float) ($quotation->total ?? $calcTot);

  // วันหมดอายุ ถ้าไม่มี ให้ว่าง
  $issue = optional($quotation->issue_date)->format('Y-m-d');
  $valid = optional($quotation->valid_until)->format('Y-m-d');
@endphp

<div class="fa-wrap">
  <div class="fa-topbar">
    <div class="fa-title">Quotation {{ $quotation->number ?? ('#'.$quotation->id) }}</div>
    <div class="fa-actions">
      <a href="{{ route('quotations.index') }}" class="fa-btn light">Back</a>
      @if(Route::has('quotations.edit'))
      <a href="{{ route('quotations.edit', $quotation) }}" class="fa-btn primary">Edit</a>
      @endif
      <form action="{{ route('quotations.convert.invoice', $quotation) }}" method="POST" style="display:inline">
        @csrf
        <button class="fa-btn light" type="submit">สร้าง Invoice จากใบนี้</button>
      </form>
      <form action="{{ route('quotations.convert.po', $quotation) }}" method="POST" style="display:inline">
        @csrf
        <button class="fa-btn light" type="submit">สร้าง PO จากใบนี้</button>
      </form>
    </div>
  </div>

  <div class="fa-grid">
    {{-- LEFT: customer + items --}}
    <div class="fa-card fa-section">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px 20px;margin-bottom:6px">
        <div>
          <span class="fa-label">Customer</span>
          <div class="fa-val">{{ $quotation->customer_name ?: '-' }}</div>
        </div>
        <div>
          <span class="fa-label">Quotation No.</span>
          <div class="fa-val">{{ $quotation->number ?? '-' }}</div>
        </div>
        <div>
          <span class="fa-label">Issue Date</span>
          <div class="fa-val">{{ $issue ?: '-' }}</div>
        </div>
        <div>
          <span class="fa-label">Valid Until</span>
          <div class="fa-val">{{ $valid ?: '-' }}</div>
        </div>
        <div>
          <span class="fa-label">Status</span>
          <div><span class="fa-badge">{{ ucfirst($quotation->status ?? 'draft') }}</span></div>
        </div>
        <div>
          <span class="fa-label">Tax Rate</span>
          <div class="fa-val">{{ number_format($quotation->tax_rate ?? 0, 2) }}%</div>
        </div>
      </div>

      @if(filled($quotation->notes))
        <div style="margin:8px 0 14px">
          <span class="fa-label">Notes</span>
          <div class="fa-val" style="white-space:pre-wrap">{{ $quotation->notes }}</div>
        </div>
      @endif

      <table class="fa-table" style="margin-top:8px">
        <thead>
          <tr>
            <th class="no">No.</th>
            <th>Items</th>
            <th class="qty">Qty</th>
            <th class="price">Unit Price</th>
            @if($hasDiscount)<th class="disc">Discount</th>@endif
            <th class="line">Line Total</th>
          </tr>
        </thead>
        <tbody>
        @forelse($items as $idx => $it)
          @php
            [$name,$desc] = $split(data_get($it,'description'));
            $qty  = (float) data_get($it,'quantity', data_get($it,'qty', 0));
            $unit = (float) data_get($it,'unit_price', data_get($it,'price', 0));
            $disc = (float) data_get($it,'discount', 0);
            $line = max(($qty * $unit) - $disc, 0);
          @endphp
          <tr>
            <td class="no">{{ $idx+1 }}</td>
            <td>
              <div class="fa-name">{{ $name ?: '-' }}</div>
              @if(filled($desc))
                <div class="fa-desc">{{ $desc }}</div>
              @endif
            </td>
            <td class="qty">{{ number_format($qty,2) }}</td>
            <td class="price">{{ $cur }}{{ number_format($unit,2) }}</td>
            @if($hasDiscount)<td class="disc">{{ $cur }}{{ number_format($disc,2) }}</td>@endif
            <td class="line">{{ $cur }}{{ number_format($line,2) }}</td>
          </tr>
        @empty
          <tr><td colspan="{{ $hasDiscount ? 6 : 5 }}" style="text-align:center;color:var(--muted)">No items</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    {{-- RIGHT: summary --}}
    <div class="fa-sticky">
      <div class="fa-card fa-section fa-totals">
        <div class="fa-title" style="font-size:16px;margin-bottom:10px;color:var(--ink)">Grand Total</div>
        <div class="row"><span>Subtotal</span><strong>{{ $cur }}{{ number_format($sub,2) }}</strong></div>
        <div class="row"><span>Tax ({{ number_format($quotation->tax_rate ?? 0, 2) }}%)</span><strong>{{ $cur }}{{ number_format($tax,2) }}</strong></div>
        <div class="row hr-dash"></div>
        <div class="row"><span>Total</span><strong>{{ $cur }}{{ number_format($tot,2) }}</strong></div>
      </div>
    </div>
  </div>
</div>
@endsection
