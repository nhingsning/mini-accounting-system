@extends('layouts.app')
@section('title','Invoice '.($invoice->number ?? ('#'.$invoice->id)))

@section('content')
@php
  $cur = config('currency.symbol','฿');
  $items = collect($invoice->items ?? []);
  $calcSub = 0.0;
  foreach ($items as $it) {
    $qty = (float) data_get($it,'quantity', data_get($it,'qty',0));
    $unit = (float) data_get($it,'unit_price', data_get($it,'price',0));
    $calcSub += ($qty * $unit);
  }
  $statusLabels = [
    'pending'   => 'Pending / Waiting for Approval',
    'approved'  => 'Approved',
    'paid'      => 'Paid',
    'cancelled' => 'Cancelled / Void',
  ];
  $statusKey = strtolower($invoice->status ?? 'pending');
  $statusText = $statusLabels[$statusKey] ?? ucfirst($invoice->status ?? 'draft');
  $taxRate = (float) ($invoice->tax_rate ?? 0);
  $sub = (float) ($invoice->subtotal ?? $calcSub);
  $tax = (float) ($invoice->tax ?? ($sub * ($taxRate/100)));
  $tot = (float) ($invoice->total ?? ($sub + $tax));
  $quoteRef = $invoice->quotation->number ?? $invoice->quotation_number ?? ($invoice->quotation?->id ? 'QT#'.$invoice->quotation->id : null);
  $issue = optional($invoice->issue_date ?? $invoice->created_at)->format('Y-m-d');
  $due = optional($invoice->due_date)->format('Y-m-d');
@endphp
<style>
:root{
  --brand:#31689E;
  --ink:#0f172a;
  --muted:#64748b;
  --line:#e5e7eb;
  --bg:#f8fafc;
  --card:#ffffff;
}
body{background:var(--bg)}
.fa-wrap{max-width:1200px;margin:0 auto;padding:22px}
.fa-topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
.fa-title{font-size:22px;font-weight:800;color:var(--ink);display:flex;gap:10px;align-items:center}
.fa-pill{display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:rgba(49,104,158,.1);color:var(--brand);border:1px solid rgba(49,104,158,.2);font-size:12px;font-weight:700;text-transform:capitalize}
.fa-actions{display:flex;flex-wrap:wrap;gap:8px}
.fa-btn{display:inline-flex;align-items:center;gap:6px;border-radius:12px;border:1px solid var(--line);padding:9px 14px;text-decoration:none;font-weight:700;font-size:14px}
.fa-btn.primary{background:var(--brand);color:#fff;border-color:var(--brand);box-shadow:0 10px 24px -12px var(--brand)}
.fa-btn.light{background:#fff;color:var(--ink)}
.fa-grid{display:grid;grid-template-columns:2fr 1.05fr;gap:18px;margin-top:10px}
@media (max-width: 1100px){.fa-grid{grid-template-columns:1fr}}
.fa-card{background:var(--card);border:1px solid var(--line);border-radius:18px;box-shadow:0 14px 48px -28px rgba(0,0,0,.25)}
.fa-section{padding:18px}
.fa-label{display:block;font-size:12px;color:var(--muted);margin-bottom:4px;letter-spacing:0.01em;text-transform:uppercase}
.fa-val{font-weight:800;color:var(--ink);font-size:15px}
.fa-mini-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px 20px;margin-bottom:8px}
.fa-summary-card{display:flex;align-items:center;justify-content:space-between;padding:16px;border-radius:16px;background:linear-gradient(135deg,rgba(49,104,158,.08),#fff);border:1px dashed rgba(49,104,158,.3)}
.fa-summary-card h3{margin:0;font-size:16px;color:var(--muted);font-weight:700}
.fa-summary-card .val{font-size:22px;font-weight:900;color:var(--ink)}
.fa-summary-card .sub{font-size:13px;color:var(--muted);margin-top:2px}
.fa-table{width:100%;border-collapse:separate;border-spacing:0 0}
.fa-table thead th{background:var(--brand);color:#fff;border:0;padding:11px 12px;font-weight:800;text-transform:uppercase;font-size:12px;letter-spacing:.02em}
.fa-table tbody td{background:#fff;border-bottom:1px solid var(--line);padding:12px;vertical-align:top;font-size:14px}
.fa-table .no{width:58px;text-align:center}
.fa-table .qty,.fa-table .price,.fa-table .line{text-align:right;width:140px}
.fa-name{font-weight:800;color:var(--ink)}
.fa-desc{color:var(--muted);font-size:13px;margin-top:4px;white-space:pre-wrap}
.fa-sticky{position:sticky;top:20px}
.fa-totals .row{display:flex;justify-content:space-between;margin:8px 0;font-size:14px;color:var(--muted)}
.fa-totals .row strong{font-weight:900;color:var(--ink)}
.fa-hint{color:var(--muted);font-size:13px;margin-top:6px}
.hr-dash{border-top:1px dashed var(--line);margin:8px 0 0}
</style>

<div class="fa-wrap">
  <div class="fa-topbar">
    <div class="fa-title">
      <span>Invoice {{ $invoice->number ?? ('#'.$invoice->id) }}</span>
      <span class="fa-pill">{{ $statusText }}</span>
      @if($quoteRef)
        <span class="fa-pill" style="background:#e8f0fb;color:var(--brand);">From {{ $quoteRef }}</span>
      @endif
    </div>
    <div class="fa-actions">
      <a href="{{ route('invoices.index') }}" class="fa-btn light">Back</a>
      @if(Route::has('invoices.edit'))
      <a href="{{ route('invoices.edit', $invoice->number ?? $invoice->id) }}" class="fa-btn primary">Edit</a>
      @endif
      <a href="{{ route('invoices.pdf', $invoice->number ?? $invoice->id) }}" class="fa-btn light" target="_blank">PDF</a>
      @php $status = strtolower($invoice->status ?? ''); @endphp
      @if(($status === 'paid' || $status === 'approved') && ($receiptsAvailable ?? false))
        @if($invoice->receipt)
          <a href="{{ route('receipts.show', $invoice->receipt->number ?? $invoice->receipt->id) }}" class="fa-btn light">View Receipt</a>
        @else
          <form action="{{ route('invoices.convert.receipt', $invoice->id) }}" method="POST" style="display:inline">
            @csrf
            <button class="fa-btn light" type="submit">สร้างใบเสร็จ</button>
          </form>
        @endif
      @endif
    </div>
  </div>

  <div class="fa-grid">
    <div class="fa-card fa-section">
      <div class="fa-summary-card" style="margin-bottom:18px">
        <div>
          <h3>Customer</h3>
          <div class="val">{{ $invoice->customer_name ?: '-' }}</div>
          <div class="sub">{{ $invoice->customer_address ?: '—' }}</div>
        </div>
        <div style="text-align:right">
          <div class="fa-label" style="margin-bottom:6px">Invoice No.</div>
          <div class="val" style="font-size:18px">{{ $invoice->number ?? '-' }}</div>
          <div class="fa-hint">Tax {{ number_format($invoice->tax_rate ?? 0,2) }}%</div>
        </div>
      </div>

      <div class="fa-mini-grid">
        <div>
          <span class="fa-label">Issue Date</span>
          <div class="fa-val">{{ $issue ?: '-' }}</div>
        </div>
        <div>
          <span class="fa-label">Due Date</span>
          <div class="fa-val">{{ $due ?: '-' }}</div>
        </div>
        <div>
          <span class="fa-label">Status</span>
          <div><span class="fa-pill" style="padding:4px 10px; font-size:11px;">{{ $statusText }}</span></div>
        </div>
        <div>
          <span class="fa-label">Quotation</span>
          @if($quoteRef)
            <a href="{{ route('quotations.show', $invoice->quotation->number ?? $invoice->quotation_number ?? $invoice->quotation->id) }}" class="fa-val" style="color:var(--brand); text-decoration:none;">{{ $quoteRef }}</a>
          @else
            <div class="fa-val">—</div>
          @endif
        </div>
      </div>

      @if(filled($invoice->notes))
        <div style="margin:12px 0 16px">
          <span class="fa-label">Notes</span>
          <div class="fa-val" style="white-space:pre-wrap">{{ $invoice->notes }}</div>
        </div>
      @endif

      <table class="fa-table" style="margin-top:8px">
        <thead>
          <tr>
            <th class="no">No.</th>
            <th>Items</th>
            <th class="qty">Qty</th>
            <th class="price">Unit Price</th>
            <th class="line">Line Total</th>
          </tr>
        </thead>
        <tbody>
        @forelse($items as $idx => $it)
          @php
            $desc = (string) data_get($it,'description','');
            $qty  = (float) data_get($it,'quantity', data_get($it,'qty', 0));
            $unit = (float) data_get($it,'unit_price', data_get($it,'price', 0));
            $line = ($qty * $unit);
          @endphp
          <tr>
            <td class="no">{{ $idx+1 }}</td>
            <td>
              <div class="fa-name">{{ $desc ?: '-' }}</div>
            </td>
            <td class="qty">{{ number_format($qty,2) }}</td>
            <td class="price">{{ $cur }}{{ number_format($unit,2) }}</td>
            <td class="line">{{ $cur }}{{ number_format($line,2) }}</td>
          </tr>
        @empty
          <tr><td colspan="5" style="text-align:center;color:var(--muted)">No items</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>

    <div class="fa-sticky">
      <div class="fa-card fa-section fa-totals">
        <div class="fa-title" style="font-size:16px;margin-bottom:10px;color:var(--ink)">Grand Total</div>
        <div class="row"><span>Subtotal</span><strong>{{ $cur }}{{ number_format($sub,2) }}</strong></div>
        <div class="row"><span>Tax ({{ number_format($taxRate, 2) }}%)</span><strong>{{ $cur }}{{ number_format($tax,2) }}</strong></div>
        <div class="row hr-dash"></div>
        <div class="row"><span>Total</span><strong>{{ $cur }}{{ number_format($tot,2) }}</strong></div>
      </div>
      <div class="fa-hint">Last updated: {{ optional($invoice->updated_at)->format('M d, Y H:i') }}</div>
    </div>
  </div>
</div>
@endsection
