@extends('layouts.app')
@section('title', __('ui.settings.layout_title'))

@php
    $layout = $layout ?? [];
    $variant = $layout['layout_variant'] ?? 'modern';
    $align = $layout['header_alignment'] ?? 'left';
    $table = $layout['table_style'] ?? 'bordered';
    $fontSize = $layout['body_font_size'] ?? 'md';
    $margins = [
        'top' => $layout['margin_top'] ?? 30,
        'bottom' => $layout['margin_bottom'] ?? 26,
        'left' => $layout['margin_left'] ?? 18,
        'right' => $layout['margin_right'] ?? 18,
    ];
    $watermark = $layout['watermark_text'] ?? '';
    $backgroundBand = $layout['background_band'] ?? true;
    $showLogo = $layout['show_logo'] ?? true;
@endphp

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')
  <main>
    <div class="topbar">
      <button id="menuToggle" class="btn btn-soft rounded-circle p-2 d-lg-none" aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>
      <div>
        <div class="fw-bold">{{ __('ui.settings.layout_title') }}</div>
        <div class="text-muted small">{{ __('ui.settings.layout_subtitle') }}</div>
      </div>
      <div class="ms-auto d-flex gap-2">
        <a href="{{ route('settings.index') }}" class="btn btn-soft btn-sm">
          <i class="bi bi-arrow-left me-1"></i>{{ __('ui.actions.back') }}
        </a>
        <button form="layoutForm" class="btn btn-brand btn-sm">
          <i class="bi bi-save me-1"></i>{{ __('ui.actions.save_changes') }}
        </button>
      </div>
    </div>

    <div class="container-fluid py-3">
      @if(session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
      @endif
      @if ($errors->any())
        <div class="alert alert-danger">
          <strong>{{ __('ui.settings.validation_error') }}</strong>
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form id="layoutForm" action="{{ route('settings.layout.update') }}" method="POST">
        @csrf
        <input type="hidden" name="background_band" value="0">
        <input type="hidden" name="show_logo" value="0">

        <div class="panel mb-3">
          <div class="panel-header">
            <div class="fw-semibold">{{ __('ui.settings.layout_presets') }}</div>
            <div class="text-muted small">{{ __('ui.settings.layout_presets_hint') }}</div>
          </div>
          <div class="panel-body row g-3">
            @foreach ([
                'modern' => __('ui.settings.layout_variant.modern'),
                'classic' => __('ui.settings.layout_variant.classic'),
                'minimal' => __('ui.settings.layout_variant.minimal'),
            ] as $key => $label)
              <div class="col-md-4">
                <label class="border rounded-3 p-3 w-100 h-100 d-flex flex-column gap-2 position-relative" style="cursor:pointer;">
                  <input class="form-check-input position-absolute" type="radio" name="layout_variant" value="{{ $key }}" @checked($variant === $key) style="right:12px; top:12px;">
                  <div class="fw-semibold">{{ $label }}</div>
                  <div class="text-muted small">{{ __('ui.settings.layout_variant_hint.' . $key) }}</div>
                </label>
              </div>
            @endforeach
          </div>
        </div>

        <div class="panel mb-3">
          <div class="panel-header d-flex align-items-center gap-3">
            <div class="fw-semibold">{{ __('ui.settings.structure_title') }}</div>
            <span class="badge text-bg-light">PDF</span>
          </div>
          <div class="panel-body row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">{{ __('ui.settings.header_alignment') }}</label>
              <div class="d-flex gap-2">
                @foreach (['left','center','right'] as $pos)
                  <label class="flex-fill btn btn-outline-secondary d-flex align-items-center justify-content-center gap-2" style="min-height:44px;">
                    <input class="form-check-input" type="radio" name="header_alignment" value="{{ $pos }}" @checked($align === $pos)>
                    <span class="text-capitalize">{{ __('ui.settings.align.' . $pos) }}</span>
                  </label>
                @endforeach
              </div>
              <div class="form-text">{{ __('ui.settings.header_alignment_hint') }}</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">{{ __('ui.settings.watermark') }}</label>
              <input type="text" class="form-control" name="watermark_text" value="{{ old('watermark_text', $watermark) }}" placeholder="CONFIDENTIAL / DRAFT">
              <div class="form-text">{{ __('ui.settings.watermark_hint') }}</div>
            </div>
            <div class="col-md-6 d-flex align-items-center gap-2">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="background_band" value="1" @checked($backgroundBand)>
                <label class="form-check-label fw-semibold">{{ __('ui.settings.background_band') }}</label>
              </div>
              <div class="text-muted small">{{ __('ui.settings.background_band_hint') }}</div>
            </div>
            <div class="col-md-6 d-flex align-items-center gap-2">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="show_logo" value="1" @checked($showLogo)>
                <label class="form-check-label fw-semibold">{{ __('ui.settings.show_logo') }}</label>
              </div>
              <div class="text-muted small">{{ __('ui.settings.show_logo_hint') }}</div>
            </div>
          </div>
        </div>

        <div class="panel mb-3">
          <div class="panel-header">
            <div class="fw-semibold">{{ __('ui.settings.table_typography') }}</div>
          </div>
          <div class="panel-body row g-3">
            <div class="col-md-4">
              <label class="form-label fw-semibold">{{ __('ui.settings.font_size') }}</label>
              <select class="form-select" name="body_font_size">
                <option value="sm" @selected($fontSize === 'sm')>{{ __('ui.settings.font.sm') }}</option>
                <option value="md" @selected($fontSize === 'md')>{{ __('ui.settings.font.md') }}</option>
                <option value="lg" @selected($fontSize === 'lg')>{{ __('ui.settings.font.lg') }}</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">{{ __('ui.settings.table_style') }}</label>
              <select class="form-select" name="table_style">
                <option value="bordered" @selected($table === 'bordered')>{{ __('ui.settings.table.bordered') }}</option>
                <option value="striped" @selected($table === 'striped')>{{ __('ui.settings.table.striped') }}</option>
                <option value="minimal" @selected($table === 'minimal')>{{ __('ui.settings.table.minimal') }}</option>
              </select>
            </div>
          </div>
        </div>

        <div class="panel mb-3">
          <div class="panel-header">
            <div class="fw-semibold">{{ __('ui.settings.margins') }}</div>
            <div class="text-muted small">{{ __('ui.settings.margins_hint') }}</div>
          </div>
          <div class="panel-body row g-3">
            @foreach (['top','bottom','left','right'] as $side)
              <div class="col-md-3">
                <label class="form-label text-capitalize">{{ __('ui.settings.margin_' . $side) }}</label>
                <div class="input-group">
                  <input type="number" min="6" max="40" class="form-control" name="margin_{{ $side }}" value="{{ old('margin_'.$side, $margins[$side]) }}">
                  <span class="input-group-text">mm</span>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </form>

      <div class="panel">
        <div class="panel-header d-flex align-items-center">
          <div class="fw-semibold">{{ __('ui.settings.layout_preview') }}</div>
          <span class="text-muted small ms-2">{{ __('ui.settings.layout_preview_hint') }}</span>
        </div>
        <div class="panel-body">
          <div id="layoutPreview" class="border rounded-3 p-4" style="background:#f8fafc; position:relative;">
            <div class="d-flex justify-content-between align-items-start mb-3" data-preview-header>
              <div class="d-flex gap-2 align-items-center">
                <div class="rounded-circle bg-primary-subtle" style="width:42px; height:42px; display:{{ $showLogo ? 'block' : 'none' }}; background: {{ ($settings['primary_color'] ?? '#31689E') }}22;"></div>
                <div>
                  <div class="fw-bold">{{ __('ui.pdf.quotation.title') }}</div>
                  <div class="text-muted small">{{ __('ui.settings.layout_preview_header') }}</div>
                </div>
              </div>
              <div class="text-end small text-muted">
                <div>#{QUO-2025}</div>
                <div>25 Nov 2025</div>
              </div>
            </div>
            <div class="bg-primary rounded-pill mb-3" style="height:4px; opacity: {{ $backgroundBand ? '0.3' : '0' }}; background: {{ $settings['primary_color'] ?? '#31689E' }};"></div>
            <div class="small text-muted mb-3">{{ __('ui.settings.layout_preview_body') }}</div>
            <table class="table table-sm" data-preview-table>
              <thead>
                <tr><th>#</th><th>{{ __('ui.pdf.labels.description') }}</th><th class="text-end">{{ __('ui.pdf.labels.qty') }}</th><th class="text-end">{{ __('ui.pdf.labels.total') }}</th></tr>
              </thead>
              <tbody>
                <tr><td>1</td><td>Item name</td><td class="text-end">1.00</td><td class="text-end">1,000.00</td></tr>
                <tr><td>2</td><td>Second line</td><td class="text-end">2.00</td><td class="text-end">1,500.00</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const preview = document.getElementById('layoutPreview');
  if(!preview) return;

  const tableStyle = document.querySelector('select[name="table_style"]');
  const fontSelect = document.querySelector('select[name="body_font_size"]');
  const alignRadios = document.querySelectorAll('input[name="header_alignment"]');
  const bandToggle = document.querySelector('input[name="background_band"]');
  const logoToggle = document.querySelector('input[name="show_logo"]');

  function refresh(){
    const table = preview.querySelector('[data-preview-table]');
    const head = preview.querySelector('[data-preview-header]');
    const logoDot = head.querySelector('.rounded-circle');

    table.classList.remove('table-bordered','table-striped');
    const style = tableStyle?.value || 'bordered';
    if(style === 'bordered'){ table.classList.add('table-bordered'); }
    if(style === 'striped'){ table.classList.add('table-striped'); }

    const font = fontSelect?.value || 'md';
    const size = font === 'lg' ? '1.05rem' : font === 'sm' ? '0.9rem' : '1rem';
    preview.style.fontSize = size;

    const align = [...alignRadios].find(r => r.checked)?.value || 'left';
    head.style.justifyContent = align === 'center' ? 'center' : 'space-between';
    head.style.textAlign = align;

    const band = bandToggle?.checked;
    const bandBar = preview.querySelector('.bg-primary');
    bandBar.style.opacity = band ? '0.3' : '0';

    const logo = logoToggle?.checked;
    logoDot.style.display = logo ? 'block' : 'none';
  }

  ['change','input'].forEach(evt => {
    document.addEventListener(evt, refresh);
  });
  refresh();
})();
</script>
@endpush
