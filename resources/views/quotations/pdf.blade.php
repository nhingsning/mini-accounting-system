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

    $companyName = trim($settings['company_name'] ?? '') ?: $title;
    $companyAddress = trim($settings['company_address'] ?? '');
    $companyPhone = trim($settings['company_phone'] ?? '');
    $companyTaxId = trim($settings['company_tax_id'] ?? '');

    $layout = is_array($settings['pdf_layout'] ?? null) ? $settings['pdf_layout'] : [];
    $marginTop = data_get($layout, 'margin_top', 30);
    $marginBottom = data_get($layout, 'margin_bottom', 26);
    $marginLeft = data_get($layout, 'margin_left', 18);
    $marginRight = data_get($layout, 'margin_right', 18);
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
    $expiry = optional($quotation->expiry_date ?? $quotation->valid_until)->format('d M Y');

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
    body { font-family: 'TH Sarabun New','Sarabun','DejaVu Sans', sans-serif; color:#111827; font-size: {{ $bodyFont }}px; }
    .watermark { position:fixed; inset:0; display:flex; align-items:center; justify-content:center; color:rgba(49,104,158,0.08); font-size:58px; font-weight:800; letter-spacing:3px; transform:rotate(-22deg); pointer-events:none; }
    .header-grid { display:grid; grid-template-columns:1.25fr 0.85fr; gap:14px; align-items:start; }
    .company-card, .doc-box, .panel { border:1px solid #d5deeb; border-radius:10px; padding:14px 16px; }
    .company-card { background:linear-gradient(120deg, rgba(49,104,158,0.08), #fff); }
    .doc-box { background:#f8fbff; border-color:{{ $primary }}22; }
    .company-name { color:{{ $primary }}; font-weight:800; font-size:22px; letter-spacing:0.5px; }
    .muted { color:#6b7280; font-size:12px; }
    .tagline { color:#334155; font-size:12px; margin-top:2px; }
    .meta-table { width:100%; border-collapse:collapse; margin-top:8px; font-size:12px; }
    .meta-table td { padding:6px 4px; border-bottom:1px solid #e5e7eb; }
    .meta-label { color:#6b7280; width:36%; }
    .meta-value { font-weight:700; color:#111827; }
    .band { height:5px; background:linear-gradient(90deg, {{ $primary }}, {{ $secondary }}); margin:14px 0 10px; opacity:{{ $showBand ? '1' : '0' }}; border-radius:999px; }
    .info-grid { display:grid; grid-template-columns:1.05fr 0.95fr; gap:14px; }
    .section-title { color:{{ $primary }}; font-weight:700; font-size:14px; margin-bottom:8px; letter-spacing:0.3px; }
    .info-table { width:100%; border-collapse:collapse; font-size:12px; }
    .info-table td { padding:5px 0; vertical-align:top; }
    .info-label { width:32%; color:#6b7280; }
    .info-value { color:#111827; font-weight:600; }
    .table { width:100%; border-collapse:collapse; margin-top:10px; }
    .table th { background: {{ $primary }}; color:#fff; padding:9px 8px; font-size:12px; text-align:left; border:1px solid {{ $primary }}; }
    .table td { border:1px solid #dbe4f0; padding:8px; font-size:12px; vertical-align:top; }
    .table tbody tr:nth-child(even){ background: {{ $tableStyle === 'striped' ? '#f8fbff' : 'transparent' }}; }
    .table td.num { text-align:right; white-space:nowrap; }
    .totals { width:320px; margin-left:auto; border:1px solid #d5deeb; border-radius:10px; overflow:hidden; font-size:12px; }
    .totals .row { display:flex; justify-content:space-between; padding:9px 12px; }
    .totals .row:nth-child(odd){ background:#f8fbff; }
    .totals .row.total { background:{{ $primary }}; color:#fff; font-weight:800; font-size:13px; }
    footer { position:fixed; bottom:{{ max($marginBottom / 2, 10) }}mm; left:{{ $marginLeft }}mm; right:{{ $marginRight }}mm; color:#6b7280; font-size:11px; border-top:1px solid #e5e7eb; padding-top:8px; }
    .signature { margin-top:28px; display:flex; justify-content:space-between; gap:20px; font-size:12px; }
    .sig-box { flex:1; border-top:1px dashed #cbd5e1; padding-top:10px; text-align:center; color:#334155; }
  </style>
</head>
<body>
  @if($watermark)
    <div class="watermark">{{ $watermark }}</div>
  @endif

  <div class="header-grid">
    <div class="company-card">
      <div style="display:flex; gap:12px; align-items:center;">
        @if($showLogo && $logoUrl)
          <img src="{{ $logoUrl }}" alt="Logo" style="height:62px; width:auto; object-fit:contain;">
        @endif
        <div>
          <div class="company-name">{{ $companyName }}</div>
          @if($headerText)
            <div class="tagline">{{ $headerText }}</div>
          @endif
        </div>
      </div>
      <table class="meta-table">
        @if($companyAddress)
          <tr><td class="meta-label">{{ __('ui.pdf.labels.address') }}</td><td class="meta-value">{{ $companyAddress }}</td></tr>
        @endif
        @if($companyPhone)
          <tr><td class="meta-label">{{ __('ui.pdf.labels.phone') }}</td><td class="meta-value">{{ $companyPhone }}</td></tr>
        @endif
        @if($companyTaxId)
          <tr><td class="meta-label">{{ __('ui.pdf.labels.tax_id') }}</td><td class="meta-value">{{ $companyTaxId }}</td></tr>
        @endif
      </table>
    </div>

    <div class="doc-box">
      <div style="display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
          <div class="muted" style="letter-spacing:0.6px;">{{ strtoupper(__('ui.pdf.quotation.subtitle')) }}</div>
          <div style="color:{{ $primary }}; font-weight:800; font-size:22px; letter-spacing:1px;">{{ strtoupper(__('ui.pdf.quotation.title')) }}</div>
        </div>
        <div style="text-align:right; font-weight:700; color:#111827;">{{ $quotation->number ?? '—' }}</div>
      </div>
      <table class="meta-table" style="margin-top:10px;">
        <tr>
          <td class="meta-label">{{ __('ui.pdf.labels.date') }}</td>
          <td class="meta-value">{{ $issue ?: '-' }}</td>
        </tr>
        <tr>
          <td class="meta-label">{{ __('ui.pdf.labels.expiry') }}</td>
          <td class="meta-value">{{ $expiry ?: '-' }}</td>
        </tr>
        @if($quotation->reference)
        <tr>
          <td class="meta-label">Ref</td>
          <td class="meta-value">{{ $quotation->reference }}</td>
        </tr>
        @endif
      </table>
    </div>
  </div>

  <div class="band"></div>
  <div class="muted" style="margin-bottom:8px;">{{ $subtitle }}</div>

  <div class="info-grid">
    <div class="panel">
      <div class="section-title">{{ __('ui.pdf.labels.customer') }}</div>
      <table class="info-table">
        <tr><td class="info-label">{{ __('ui.pdf.labels.customer') }}</td><td class="info-value">{{ $quotation->customer_name ?? '—' }}</td></tr>
        <tr><td class="info-label">{{ __('ui.pdf.labels.address') }}</td><td class="info-value" style="white-space:pre-wrap;">{{ $quotation->customer_address ?? '—' }}</td></tr>
        @if($quotation->customer_tax_id)
          <tr><td class="info-label">{{ __('ui.pdf.labels.tax_id') }}</td><td class="info-value">{{ $quotation->customer_tax_id }}</td></tr>
        @endif
        @if($quotation->customer_branch_code)
          <tr><td class="info-label">{{ __('ui.pdf.labels.branch') }}</td><td class="info-value">{{ $quotation->customer_branch_code }}</td></tr>
        @endif
      </table>
    </div>

    <div class="panel">
      <div class="section-title">{{ __('ui.pdf.labels.notes') }}</div>
      <table class="info-table">
        <tr><td class="info-label">{{ __('ui.pdf.labels.phone') }}</td><td class="info-value">{{ $quotation->contact_phone ?? ($quotation->customer_phone ?? '—') }}</td></tr>
        <tr><td class="info-label">{{ __('ui.pdf.labels.email') }}</td><td class="info-value">{{ $quotation->contact_email ?? '—' }}</td></tr>
        <tr><td class="info-label">Attn.</td><td class="info-value">{{ $quotation->contact_name ?? '—' }}</td></tr>
        <tr><td class="info-label">{{ __('ui.pdf.labels.notes') }}</td><td class="info-value" style="white-space:pre-wrap;">{{ $quotation->notes ?? '—' }}</td></tr>
      </table>
    </div>
  </div>

  <table class="table" style="margin-top:16px;">
    <thead>
      <tr>
        <th style="width:40px;">#</th>
        <th>{{ __('ui.pdf.labels.description') }}</th>
        <th style="width:80px; text-align:right;">{{ __('ui.pdf.labels.qty') }}</th>
        <th style="width:110px; text-align:right;">{{ __('ui.pdf.labels.unit_price') }}</th>
        <th style="width:120px; text-align:right;">{{ __('ui.pdf.labels.line_total') }}</th>
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
      <div class="row"><span>{{ __('ui.pdf.labels.subtotal') }}</span><span>{{ $cur }}{{ number_format($subtotal,2) }}</span></div>
      <div class="row"><span>{{ __('ui.pdf.labels.tax') }} ({{ number_format($taxRate,2) }}%)</span><span>{{ $cur }}{{ number_format($tax,2) }}</span></div>
      <div class="row total"><span>{{ __('ui.pdf.labels.total') }}</span><span>{{ $cur }}{{ number_format($total,2) }}</span></div>
    </div>
  </div>

  <div class="signature">
    <div class="sig-box">{{ __('ui.pdf.labels.customer') }} / {{ __('ui.pdf.labels.notes') }}</div>
    <div class="sig-box">{{ __('ui.pdf.labels.company') }}</div>
  </div>

  @if($footerText)
    <footer>{{ $footerText }}</footer>
  @endif
</body>
</html>
