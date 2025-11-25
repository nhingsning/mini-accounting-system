@php
    use Illuminate\Support\Facades\Storage;

    $settings = $appSettings ?? [];
    $primary = $settings['primary_color'] ?? '#31689E';
    $headerText = trim($settings['header_text'] ?? '');
    $footerText = trim($settings['footer_text'] ?? '');
    $logoPath = $settings['logo_path'] ?? null;
    $logoDataUrl = $settings['logo_data_url'] ?? ($settings['logo'] ?? null);

    if (! $logoDataUrl && $logoPath && Storage::disk('public')->exists($logoPath)) {
        $mime = Storage::disk('public')->mimeType($logoPath) ?: 'image/png';
        $logoDataUrl = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($logoPath));
    }

    $companyName = trim($settings['company_name'] ?? '') ?: __('ui.pdf.company_header');
    $companyAddress = trim($settings['company_address'] ?? '');
    $companyPhone = trim($settings['company_phone'] ?? '');
    $companyTaxId = trim($settings['company_tax_id'] ?? '');

    $layout = is_array($settings['pdf_layout'] ?? null) ? $settings['pdf_layout'] : [];
    $marginTop = data_get($layout, 'margin_top', 30);
    $marginBottom = data_get($layout, 'margin_bottom', 26);
    $marginLeft = data_get($layout, 'margin_left', 18);
    $marginRight = data_get($layout, 'margin_right', 18);
    $bodyFont = match (data_get($layout, 'body_font_size', 'md')) {
        'sm' => 12,
        'lg' => 16,
        default => 14,
    };

    $items = collect($quotation->items ?? []);
    $cur = config('currency.symbol','฿');
    $issue = optional($quotation->issue_date ?? $quotation->created_at)->format('d/m/Y');
    $expiry = optional($quotation->expiry_date ?? $quotation->valid_until)->format('d/m/Y');
    $preparedOn = optional($quotation->created_at)->format('d/m/Y');
    $preparedBy = $quotation->salesperson
        ?? ($quotation->contact_name ?? ($quotation->created_by ?? null))
        ?? '—';
    $approvedBy = $quotation->approved_by ?? data_get($quotation, 'approver_name') ?? '—';
    $approvedOn = optional($quotation->approved_at ?? $quotation->updated_at)->format('d/m/Y');
    $remark = trim($quotation->notes ?? '') ?: '—';

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
    .muted { color:#6b7280; font-size:12px; }
    .small { font-size:12px; }
    .header { border:1px solid #d5deeb; border-radius:10px; overflow:hidden; }
    .header-top { background: linear-gradient(135deg, {{ $primary }}, {{ $secondary }}); color:#fff; padding:10px 16px; display:flex; align-items:center; gap:12px; }
    .header-body { display:grid; grid-template-columns:1.1fr 0.9fr; gap:0; border-top:1px solid #d5deeb; }
    .header-left { padding:14px 16px; border-right:1px solid #d5deeb; }
    .header-right { padding:14px 16px; background:#f8fbff; }
    .company-name { font-size:22px; font-weight:800; margin:0; }
    .doc-title { font-size:24px; font-weight:800; color:{{ $primary }}; margin:0; text-align:right; letter-spacing:0.6px; }
    .meta-table { width:100%; border-collapse:collapse; margin-top:6px; }
    .meta-table td { padding:4px 0; font-size:12px; }
    .meta-label { width:36%; color:#6b7280; }
    .meta-value { font-weight:700; color:#111827; }
    .band { height:6px; background:{{ $primary }}; margin:12px 0; }
    .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px; }
    .panel { border:1px solid #d5deeb; border-radius:10px; padding:12px 14px; }
    .section-title { color:{{ $primary }}; font-weight:700; font-size:14px; margin-bottom:8px; }
    .info-table { width:100%; border-collapse:collapse; }
    .info-table td { padding:5px 0; vertical-align:top; font-size:12px; }
    .info-label { width:32%; color:#6b7280; }
    .info-value { color:#111827; font-weight:600; white-space:pre-wrap; }
    .table { width:100%; border-collapse:collapse; margin-top:6px; }
    .table th { background: {{ $primary }}; color:#fff; padding:8px 8px; font-size:12px; text-align:left; border:1px solid {{ $primary }}; }
    .table td { border:1px solid #dbe4f0; padding:8px; font-size:12px; vertical-align:top; }
    .table td.num, .table th.num { text-align:right; white-space:nowrap; }
    .totals { width:340px; margin-left:auto; margin-top:12px; border:1px solid #d5deeb; border-radius:10px; overflow:hidden; font-size:12px; }
    .totals .row { display:flex; justify-content:space-between; padding:9px 12px; }
    .totals .row:nth-child(odd){ background:#f8fbff; }
    .totals .row.total { background:{{ $primary }}; color:#fff; font-weight:800; font-size:13px; }
    .foot-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:12px; }
    .muted-card { background:#f8fbff; border:1px solid #d5deeb; border-radius:10px; padding:12px 14px; font-size:12px; color:#334155; min-height:70px; }
    .meta-table-small td { padding:4px 0; font-size:12px; }
    footer { position:fixed; bottom:{{ max($marginBottom / 2, 10) }}mm; left:{{ $marginLeft }}mm; right:{{ $marginRight }}mm; color:#6b7280; font-size:11px; border-top:1px solid #e5e7eb; padding-top:8px; }
    .signature { margin-top:20px; display:flex; justify-content:space-between; gap:20px; font-size:12px; }
    .sig-box { flex:1; border-top:1px dashed #cbd5e1; padding-top:10px; text-align:center; color:#334155; }
  </style>
</head>
<body>
  <div class="header">
    <div class="header-top">
      @if($logoDataUrl)
        <img src="{{ $logoDataUrl }}" alt="Logo" style="height:50px; width:auto; object-fit:contain;">
      @endif
      <div>
        <p class="company-name">{{ $companyName }}</p>
        @if($headerText)
          <div class="small">{{ $headerText }}</div>
        @endif
      </div>
    </div>
    <div class="header-body">
      <div class="header-left">
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
      <div class="header-right">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
          <div class="muted" style="letter-spacing:0.8px; text-transform:uppercase;">{{ __('ui.pdf.quotation.subtitle') }}</div>
          <div style="text-align:right;">
            <p class="doc-title">{{ __('ui.pdf.quotation.title') }}</p>
            <div style="font-weight:800; color:#111827;">{{ $quotation->number ?? '—' }}</div>
          </div>
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
  </div>

  <div class="band"></div>

  <div class="info-grid">
    <div class="panel">
      <div class="section-title">{{ __('ui.pdf.labels.customer') }}</div>
      <table class="info-table">
        <tr><td class="info-label">{{ __('ui.pdf.labels.customer') }}</td><td class="info-value">{{ $quotation->customer_name ?? '—' }}</td></tr>
        <tr><td class="info-label">{{ __('ui.pdf.labels.address') }}</td><td class="info-value">{{ $quotation->customer_address ?? '—' }}</td></tr>
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
        <tr><td class="info-label">{{ __('ui.pdf.labels.notes') }}</td><td class="info-value">{{ $quotation->notes ?? '—' }}</td></tr>
      </table>
    </div>
  </div>

  <table class="table">
    <thead>
      <tr>
        <th class="num" style="width:40px;">#</th>
        <th>{{ __('ui.pdf.labels.description') }}</th>
        <th class="num" style="width:80px;">{{ __('ui.pdf.labels.qty') }}</th>
        <th class="num" style="width:110px;">{{ __('ui.pdf.labels.unit_price') }}</th>
        <th class="num" style="width:120px;">{{ __('ui.pdf.labels.line_total') }}</th>
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

  <div class="foot-grid">
    <div class="muted-card">
      <div class="section-title" style="margin-top:0;">{{ __('ui.pdf.labels.remark') }}</div>
      <div style="white-space:pre-wrap;">{{ $remark }}</div>
    </div>
    <div class="panel" style="background:#fff;">
      <div class="section-title" style="margin-top:0;">{{ __('ui.pdf.labels.signoff') }}</div>
      <table class="meta-table meta-table-small">
        <tr><td class="meta-label">{{ __('ui.pdf.labels.prepared_by') }}</td><td class="meta-value">{{ $preparedBy }}</td></tr>
        <tr><td class="meta-label">{{ __('ui.pdf.labels.created_date') }}</td><td class="meta-value">{{ $preparedOn ?: '—' }}</td></tr>
        <tr><td class="meta-label">{{ __('ui.pdf.labels.approved_by') }}</td><td class="meta-value">{{ $approvedBy }}</td></tr>
        <tr><td class="meta-label">{{ __('ui.pdf.labels.approved_date') }}</td><td class="meta-value">{{ $approvedOn ?: '—' }}</td></tr>
      </table>
    </div>
  </div>

  <div class="signature">
    <div class="sig-box">{{ __('ui.pdf.labels.customer') }}</div>
    <div class="sig-box">{{ __('ui.pdf.labels.company') }}</div>
  </div>

  @if($footerText)
    <footer>{{ $footerText }}</footer>
  @endif
</body>
</html>
