@php
    use Illuminate\Support\Facades\Storage;

    $settings = $appSettings ?? [];
    $primary = $settings['primary_color'] ?? '#31689E';
    $headerText = $settings['header_text'] ?? __('ui.pdf.company_header');
    $footerText = $settings['footer_text'] ?? '';
    $logoPath = $settings['logo_path'] ?? null;
    $logoUrl = $logoPath ? Storage::disk('public')->url($logoPath) : null;
    $title = __('ui.pdf.quotation.title');
    $subtitle = __('ui.pdf.quotation.subtitle');

    $layout = is_array($settings['pdf_layout'] ?? null) ? $settings['pdf_layout'] : [];
    $marginTop = data_get($layout, 'margin_top', 30);
    $marginBottom = data_get($layout, 'margin_bottom', 26);
    $marginLeft = data_get($layout, 'margin_left', 18);
    $marginRight = data_get($layout, 'margin_right', 18);
    $headerAlign = data_get($layout, 'header_alignment', 'left');
    $tableStyle = data_get($layout, 'table_style', 'bordered');
    $bodyFont = match (data_get($layout, 'body_font_size', 'md')) {
        'sm' => 12,
        'lg' => 16,
        default => 14,
    };
    $showBand = (bool) data_get($layout, 'background_band', true);
    $showLogo = (bool) data_get($layout, 'show_logo', true);
    $watermark = trim((string) data_get($layout, 'watermark_text', ''));

    $items = collect($quotation->items ?? []);
    $cur = config('currency.symbol','฿');
    $issue = optional($quotation->issue_date ?? $quotation->created_at)->format('d M Y');
    $expiry = optional($quotation->expiry_date)->format('d M Y');

    $subtotal = 0.0;
    foreach ($items as $it) {
      $qty  = (float) data_get($it, 'quantity', data_get($it,'qty',0));
      $unit = (float) data_get($it, 'unit_price', data_get($it,'price',0));
      $subtotal += ($qty * $unit);
    }
    $taxRate = (float)($quotation->tax_rate ?? 0);
    $tax  = $quotation->tax ?? ($subtotal * ($taxRate/100));
    $total= $quotation->total ?? ($subtotal + $tax);

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
    @page { margin: {{ $marginTop }}mm {{ $marginRight }}mm {{ $marginBottom }}mm {{ $marginLeft }}mm; }
    * { box-sizing:border-box; }
    body { font-family: 'TH Sarabun New','Sarabun','DejaVu Sans', sans-serif; color:#1f2937; font-size: {{ $bodyFont }}px; }
    .brand { color: {{ $primary }}; font-weight:800; font-size:26px; letter-spacing:0.5px; }
    .muted { color:#6b7280; font-size:12px; }
    .grid { display:grid; grid-template-columns:1.2fr 1fr; gap:18px; }
    .card { border:1px solid #dbe4f0; border-radius:10px; padding:14px 16px; }
    .table { width:100%; border-collapse:collapse; margin-top:6px; }
    .table th { background: {{ $primary }}; color:#fff; padding:10px 8px; font-size:12px; text-align:left; border: {{ $tableStyle === 'minimal' ? '0' : '1px solid '.$primary }}; }
    .table td { border: {{ $tableStyle === 'minimal' ? '1px solid transparent' : '1px solid #dbe4f0' }}; border-bottom:1px solid #dbe4f0; padding:8px; font-size:12px; vertical-align:top; }
    .table tbody tr:nth-child(even){ background: {{ $tableStyle === 'striped' ? '#f8fbff' : 'transparent' }}; }
    .table td.num { text-align:right; white-space:nowrap; }
    .section-title { color: {{ $primary }}; font-weight:700; margin-bottom:6px; font-size:14px; }
    .totals { width:260px; margin-left:auto; border:1px solid #dbe4f0; border-radius:10px; overflow:hidden; }
    .totals .row { display:flex; justify-content:space-between; padding:8px 10px; font-size:12px; }
    .totals .row:nth-child(even){ background:#f5f8fc; }
    .totals .row strong { font-size:13px; }
    .divider { height:4px; background: {{ $secondary }}; margin:16px 0 12px; opacity: {{ $showBand ? '0.35' : '0' }}; border-radius:999px; }
    header { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
    header.header-center { flex-direction:column; align-items:center; text-align:center; }
    header.header-right { flex-direction:row-reverse; text-align:right; }
    .meta-block { min-width:180px; }
    header.header-center .meta-block { text-align:center; }
    header.header-right .meta-block { text-align:right; }
    .logo-box { display:flex; gap:12px; align-items:center; }
    footer { position:fixed; bottom:{{ max($marginBottom / 2, 10) }}mm; left:{{ $marginLeft }}mm; right:{{ $marginRight }}mm; color:#6b7280; font-size:11px; border-top:1px solid #e5e7eb; padding-top:8px; }
    .watermark { position:fixed; inset:0; display:flex; align-items:center; justify-content:center; color:rgba(31,41,55,0.08); font-size:52px; font-weight:700; letter-spacing:2px; transform:rotate(-26deg); pointer-events:none; }
  </style>
</head>
<body>
  <header class="header header-{{ $headerAlign }}">
    <div class="logo-box">
      @if($showLogo && $logoUrl)
        <img src="{{ $logoUrl }}" alt="Logo" style="height:54px; width:auto; object-fit:contain;">
      @endif
      <div>
        <div class="brand">{{ $title }}</div>
        <div class="muted" style="margin-top:4px; line-height:1.45; max-width:320px;">{{ $headerText }}</div>
      </div>
    </div>
    <div class="meta-block" style="text-align:right; font-size:12px; color:#1f2937; line-height:1.6;">
      <div><strong>{{ __('ui.pdf.labels.date') }}:</strong> {{ $issue ?: '-' }}</div>
      <div><strong>{{ __('ui.pdf.labels.quotation_no') }}:</strong> {{ $quotation->number ?? '-' }}</div>
      <div><strong>{{ __('ui.pdf.labels.expiry') }}:</strong> {{ $expiry ?: '-' }}</div>
    </div>
  </header>

  @if($watermark)
    <div class="watermark">{{ $watermark }}</div>
  @endif

  <div class="divider"></div>
  <div class="muted" style="margin-bottom:10px;">{{ $subtitle }}</div>

  <div class="grid">
    <div class="card">
      <div class="section-title">{{ __('ui.pdf.labels.customer') }}</div>
      <div style="font-weight:700; font-size:13px;">{{ $quotation->customer_name ?? '-' }}</div>
      <div class="muted" style="margin-top:6px; line-height:1.6; white-space:pre-wrap;">
        {{ $quotation->customer_address ?? '—' }}
      </div>
      @if($quotation->customer_tax_id)
        <div class="muted" style="margin-top:6px;">{{ __('ui.pdf.labels.tax_id') }}: {{ $quotation->customer_tax_id }}</div>
      @endif
      @if($quotation->customer_branch_code)
        <div class="muted">{{ __('ui.pdf.labels.branch') }}: {{ $quotation->customer_branch_code }}</div>
      @endif
    </div>

    <div class="card">
      <div class="section-title">{{ __('ui.pdf.labels.notes') }}</div>
      <div style="min-height:68px; font-size:12px; line-height:1.6; white-space:pre-wrap;">
        {{ $quotation->notes ?? '—' }}
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
      <div class="row"><span>{{ __('ui.pdf.labels.subtotal') }}:</span><strong>{{ $cur }}{{ number_format($subtotal,2) }}</strong></div>
      <div class="row"><span>{{ __('ui.pdf.labels.tax') }} ({{ number_format($taxRate,2) }}%):</span><strong>{{ $cur }}{{ number_format($tax,2) }}</strong></div>
      <div class="row" style="font-weight:800; background: {{ $primary }}; color:#fff;"><span>{{ __('ui.pdf.labels.total') }}:</span><span>{{ $cur }}{{ number_format($total,2) }}</span></div>
    </div>
  </div>

  @if($footerText)
    <footer>{{ $footerText }}</footer>
  @endif
</body>
</html>
