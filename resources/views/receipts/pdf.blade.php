@php
    use Illuminate\Support\Facades\Storage;

    $settings = $appSettings ?? [];
    $primary = $settings['primary_color'] ?? '#31689E';
    $headerText = $settings['header_text'] ?? __('ui.pdf.company_header');
    $footerText = $settings['footer_text'] ?? '';
    $logoPath = $settings['logo_path'] ?? null;
    $logoUrl = $logoPath ? Storage::disk('public')->url($logoPath) : null;
    $title = __('ui.pdf.receipt.title');
    $subtitle = __('ui.pdf.receipt.subtitle');

    $items = collect(optional($receipt->invoice)->items ?? []);
    $cur = config('currency.symbol','฿');
    $issue = optional($receipt->issue_date ?? $receipt->created_at)->format('d M Y');
    $invoiceNumber = $receipt->invoice_number ?? optional($receipt->invoice)->number;

    $subtotal = 0.0;
    foreach ($items as $it) {
      $qty  = (float) data_get($it, 'quantity', data_get($it,'qty',0));
      $unit = (float) data_get($it, 'unit_price', data_get($it,'price',0));
      $subtotal += ($qty * $unit);
    }
    $taxRate = 0;
    $tax  = 0;
    $total= $receipt->total ?? $subtotal;

    $secondary = $primary;
    $hex = ltrim($primary, '#');
    if (strlen($hex) === 6) {
        $r = min(255, hexdec(substr($hex,0,2)) + 28);
        $g = min(255, hexdec(substr($hex,2,2)) + 28);
        $b = min(255, hexdec(substr($hex,4,2)) + 28);
        $secondary = sprintf('#%02X%02X%02X', $r, $g, $b);
    }
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="UTF-8">
  <style>
    @page { margin: 30mm 18mm 26mm 18mm; }
    * { box-sizing:border-box; }
    body { font-family: 'TH Sarabun New','Sarabun','DejaVu Sans', sans-serif; color:#1f2937; }
    .brand { color: {{ $primary }}; font-weight:800; font-size:26px; letter-spacing:0.5px; }
    .muted { color:#6b7280; font-size:12px; }
    .grid { display:grid; grid-template-columns:1.2fr 1fr; gap:18px; }
    .card { border:1px solid #dbe4f0; border-radius:10px; padding:14px 16px; }
    .table { width:100%; border-collapse:collapse; margin-top:6px; }
    .table th { background: {{ $primary }}; color:#fff; padding:10px 8px; font-size:12px; text-align:left; }
    .table td { border:1px solid #dbe4f0; padding:8px; font-size:12px; vertical-align:top; }
    .table td.num { text-align:right; white-space:nowrap; }
    .section-title { color: {{ $primary }}; font-weight:700; margin-bottom:6px; font-size:14px; }
    .totals { width:260px; margin-left:auto; border:1px solid #dbe4f0; border-radius:10px; overflow:hidden; }
    .totals .row { display:flex; justify-content:space-between; padding:8px 10px; font-size:12px; }
    .totals .row:nth-child(even){ background:#f5f8fc; }
    .totals .row strong { font-size:13px; }
    .divider { height:4px; background: {{ $secondary }}; margin:16px 0 12px; opacity:0.35; border-radius:999px; }
    header { display:flex; justify-content:space-between; align-items:flex-start; }
    .logo-box { display:flex; gap:12px; align-items:center; }
    footer { position:fixed; bottom:14mm; left:18mm; right:18mm; color:#6b7280; font-size:11px; border-top:1px solid #e5e7eb; padding-top:8px; }
  </style>
</head>
<body>
  <header>
    <div class="logo-box">
      @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="Logo" style="height:54px; width:auto; object-fit:contain;">
      @endif
      <div>
        <div class="brand">{{ $title }}</div>
        <div class="muted" style="margin-top:4px; line-height:1.45; max-width:320px;">{{ $headerText }}</div>
      </div>
    </div>
    <div style="text-align:right; font-size:12px; color:#1f2937; line-height:1.6;">
      <div><strong>{{ __('ui.pdf.labels.date') }}:</strong> {{ $issue ?: '-' }}</div>
      <div><strong>{{ __('ui.pdf.labels.receipt_no') }}:</strong> {{ $receipt->number ?? '-' }}</div>
      <div><strong>{{ __('ui.pdf.labels.invoice_no') }}:</strong> {{ $invoiceNumber ?? '—' }}</div>
    </div>
  </header>

  <div class="divider"></div>
  <div class="muted" style="margin-bottom:10px;">{{ $subtitle }}</div>

  <div class="grid">
    <div class="card">
      <div class="section-title">{{ __('ui.pdf.labels.customer') }}</div>
      <div style="font-weight:700; font-size:13px;">{{ $receipt->customer_name ?? '-' }}</div>
      <div class="muted" style="margin-top:6px; line-height:1.6; white-space:pre-wrap;">
        {{ $receipt->customer_address ?? '—' }}
      </div>
      @if($receipt->customer_tax_id)
        <div class="muted" style="margin-top:6px;">{{ __('ui.pdf.labels.tax_id') }}: {{ $receipt->customer_tax_id }}</div>
      @endif
      @if($receipt->customer_branch_code)
        <div class="muted">{{ __('ui.pdf.labels.branch') }}: {{ $receipt->customer_branch_code }}</div>
      @endif
    </div>

    <div class="card">
      <div class="section-title">{{ __('ui.pdf.labels.notes') }}</div>
      <div style="min-height:68px; font-size:12px; line-height:1.6; white-space:pre-wrap;">
        {{ $receipt->notes ?? '—' }}
      </div>
    </div>
  </div>

  <table class="table" style="margin-top:14px;">
    <thead>
      <tr>
        <th style="width:34px;">#</th>
        <th>{{ __('ui.pdf.labels.description') }}</th>
        <th style="width:70px; text-align:right;">{{ __('ui.pdf.labels.qty') }}</th>
        <th style="width:90px; text-align:right;">{{ __('ui.pdf.labels.unit_price') }}</th>
        <th style="width:100px; text-align:right;">{{ __('ui.pdf.labels.line_total') }}</th>
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
          <td colspan="5" style="text-align:center; padding:12px; color:#6b7280;">{{ __('ui.pdf.labels.no_items') }}</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <div style="display:flex; justify-content:flex-end; margin-top:12px;">
    <div class="totals">
      <div class="row"><span>{{ __('ui.pdf.labels.total_received') }}:</span><strong>{{ $cur }}{{ number_format($total,2) }}</strong></div>
    </div>
  </div>

  @if($footerText)
    <footer>{{ $footerText }}</footer>
  @endif
</body>
</html>
