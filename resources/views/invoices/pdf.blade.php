@php
  $items = collect($invoice->items ?? []);
  $cur = config('currency.symbol','฿');
  $issue = optional($invoice->issue_date ?? $invoice->created_at)->format('d M Y');
  $due = optional($invoice->due_date)->format('d M Y');
  $taxRate = (float)($invoice->tax_rate ?? 0);

  $subtotal = 0.0;
  foreach ($items as $it) {
    $qty  = (float) data_get($it, 'quantity', data_get($it,'qty',0));
    $unit = (float) data_get($it, 'unit_price', data_get($it,'price',0));
    $subtotal += ($qty * $unit);
  }
  $tax  = $invoice->tax ?? ($subtotal * ($taxRate/100));
  $total= $invoice->total ?? ($subtotal + $tax);
@endphp
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    @page { margin: 28mm 18mm 24mm 18mm; }
    * { box-sizing:border-box; }
    body { font-family: 'DejaVu Sans', 'sarabun', sans-serif; color:#1f2937; }
    .brand { color:#31689E; font-weight:800; font-size:26px; letter-spacing:1px; }
    .muted { color:#6b7280; font-size:12px; }
    .grid { display:grid; grid-template-columns:1.2fr 1fr; gap:18px; }
    .card { border:1px solid #dbe4f0; border-radius:10px; padding:14px 16px; }
    .table { width:100%; border-collapse:collapse; margin-top:6px; }
    .table th { background:#31689E; color:#fff; padding:10px 8px; font-size:12px; text-align:left; }
    .table td { border:1px solid #dbe4f0; padding:8px; font-size:12px; vertical-align:top; }
    .table td.num { text-align:right; white-space:nowrap; }
    .section-title { color:#31689E; font-weight:700; margin-bottom:6px; font-size:14px; }
    .totals { width:260px; margin-left:auto; border:1px solid #dbe4f0; border-radius:10px; overflow:hidden; }
    .totals .row { display:flex; justify-content:space-between; padding:8px 10px; font-size:12px; }
    .totals .row:nth-child(even){ background:#f5f8fc; }
    .totals .row strong { font-size:13px; }
    .divider { height:4px; background:#31689E; margin:16px 0 10px; opacity:0.2; }
  </style>
</head>
<body>
  <div style="display:flex; justify-content:space-between; align-items:flex-start;">
    <div>
      <div class="brand">Invoice</div>
      <div class="muted" style="margin-top:4px; max-width:260px; line-height:1.5;">
        Company Name<br>
        Primary Business Address<br>
        Address 22<br>
        Phone: 555-555-5555 &nbsp; Fax: 555-555-5556<br>
        Email: someone@example.com
      </div>
    </div>
    <div style="text-align:right; font-size:12px; color:#1f2937; line-height:1.6;">
      <div><strong>Date:</strong> {{ $issue ?: '-' }}</div>
      <div><strong>Invoice:</strong> {{ $invoice->number ?? '-' }}</div>
      <div><strong>Quotation:</strong> {{ $invoice->quotation_number ?? ($invoice->quotation->number ?? '—') }}</div>
      <div><strong>Due Date:</strong> {{ $due ?: '-' }}</div>
    </div>
  </div>

  <div class="divider"></div>

  <div class="grid">
    <div class="card">
      <div class="section-title">Customer</div>
      <div style="font-weight:700; font-size:13px;">{{ $invoice->customer_name ?? '-' }}</div>
      <div class="muted" style="margin-top:6px; line-height:1.6; white-space:pre-wrap;">
        {{ $invoice->customer_address ?? 'Address' }}
      </div>
      @if($invoice->customer_tax_id)
        <div class="muted" style="margin-top:6px;">Tax ID: {{ $invoice->customer_tax_id }}</div>
      @endif
      @if($invoice->customer_branch_code)
        <div class="muted">Branch: {{ $invoice->customer_branch_code }}</div>
      @endif
    </div>

    <div class="card">
      <div class="section-title">Notes or Special Comments</div>
      <div style="min-height:68px; font-size:12px; line-height:1.6; white-space:pre-wrap;">
        {{ $invoice->notes ?? '—' }}
      </div>
    </div>
  </div>

  <table class="table" style="margin-top:14px;">
    <thead>
      <tr>
        <th style="width:34px;">#</th>
        <th>Description</th>
        <th style="width:70px; text-align:right;">Qty</th>
        <th style="width:90px; text-align:right;">Unit Price</th>
        <th style="width:100px; text-align:right;">Total</th>
      </tr>
    </thead>
    <tbody>
      @forelse($items as $index => $item)
        @php
          $desc = (string) data_get($item,'description','');
          $qty  = (float) data_get($item,'quantity', data_get($item,'qty',0));
          $unit = (float) data_get($item,'unit_price', data_get($item,'price',0));
          $line = ($qty * $unit);
        @endphp
        <tr>
          <td class="num">{{ $index + 1 }}</td>
          <td>{{ $desc ?: '—' }}</td>
          <td class="num">{{ number_format($qty,2) }}</td>
          <td class="num">{{ $cur }}{{ number_format($unit,2) }}</td>
          <td class="num">{{ $cur }}{{ number_format($line,2) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5" style="text-align:center; padding:12px; color:#6b7280;">No items</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div style="display:flex; justify-content:flex-end; margin-top:12px;">
    <div class="totals">
      <div class="row"><span>Sub Total:</span><strong>{{ $cur }}{{ number_format($subtotal,2) }}</strong></div>
      <div class="row"><span>Tax ({{ number_format($taxRate,2) }}%):</span><strong>{{ $cur }}{{ number_format($tax,2) }}</strong></div>
      <div class="row" style="font-weight:800; background:#31689E; color:#fff;"><span>Total:</span><span>{{ $cur }}{{ number_format($total,2) }}</span></div>
    </div>
  </div>
</body>
</html>
