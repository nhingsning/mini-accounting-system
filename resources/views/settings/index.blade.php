@extends('layouts.app')
@section('title', __('ui.menu.settings'))

@php
    $primary = $appSettings['primary_color'] ?? '#31689E';
@endphp

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')
  <main>
    <div class="topbar">
      <button id="menuToggle" class="btn btn-soft rounded-circle p-2 d-lg-none" aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>
      <div class="d-flex align-items-center gap-2">
        <div>
          <div class="fw-bold">{{ __('ui.settings.title') }}</div>
          <div class="text-muted small">{{ __('ui.settings.subtitle') }}</div>
        </div>
      </div>
      <div class="ms-auto d-flex align-items-center gap-2">
        <a href="{{ route('settings.layout') }}" class="btn btn-soft btn-sm">
          <i class="bi bi-magic me-1"></i>{{ __('ui.settings.layout_title') }}
        </a>
        <span class="badge text-bg-light">{{ __('ui.settings.language_default') }}: {{ strtoupper($appSettings['default_language'] ?? app()->getLocale()) }}</span>
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

      <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="panel mb-3">
          <div class="panel-header">
            <div class="fw-semibold">{{ __('ui.settings.branding_title') }}</div>
            <button type="submit" class="btn btn-brand btn-sm">
              <i class="bi bi-save me-1"></i>{{ __('ui.actions.save_changes') }}
            </button>
          </div>
          <div class="panel-body row g-4">
            <div class="col-md-4">
              <label class="form-label fw-semibold">{{ __('ui.settings.logo') }}</label>
              <div class="border rounded p-3 text-center" style="background:#f8fafc;">
                @if($logoUrl)
                  <img src="{{ $logoUrl }}" alt="Logo" class="img-fluid mb-2" style="max-height:120px; object-fit:contain;">
                @else
                  <div class="text-muted">{{ __('ui.settings.logo_placeholder') }}</div>
                @endif
                <input type="file" class="form-control mt-2" name="logo" accept="image/*">
                <div class="form-text">{{ __('ui.settings.logo_helper') }}</div>
              </div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">{{ __('ui.settings.primary_color') }}</label>
              <div class="d-flex align-items-center gap-2">
                <input type="color" name="primary_color" value="{{ old('primary_color', $primary) }}" class="form-control form-control-color" style="width:64px; height:48px;">
                <input type="text" value="{{ old('primary_color', $primary) }}" class="form-control" placeholder="#31689E" oninput="this.previousElementSibling.value=this.value">
              </div>
              <div class="form-text">{{ __('ui.settings.color_helper') }}</div>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">{{ __('ui.settings.default_language') }}</label>
              <select class="form-select" name="default_language">
                <option value="th" @selected(($appSettings['default_language'] ?? app()->getLocale()) === 'th')>{{ __('ui.language.th') }}</option>
                <option value="en" @selected(($appSettings['default_language'] ?? app()->getLocale()) === 'en')>{{ __('ui.language.en') }}</option>
              </select>
              <div class="form-text">{{ __('ui.settings.language_helper') }}</div>
            </div>
          </div>
        </div>

        <div class="panel">
          <div class="panel-header">
            <div class="fw-semibold">{{ __('ui.settings.document_template') }}</div>
            <button type="submit" class="btn btn-brand btn-sm">
              <i class="bi bi-save me-1"></i>{{ __('ui.actions.save') }}
            </button>
          </div>
          <div class="panel-body row g-4">
            <div class="col-md-6">
              <label class="form-label fw-semibold">{{ __('ui.settings.header_text') }}</label>
              <input type="text" class="form-control" name="header_text" value="{{ old('header_text', $appSettings['header_text'] ?? '') }}" placeholder="{{ __('ui.settings.header_placeholder') }}">
              <div class="form-text">{{ __('ui.settings.header_helper') }}</div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">{{ __('ui.settings.footer_text') }}</label>
              <textarea class="form-control" rows="3" name="footer_text" placeholder="{{ __('ui.settings.footer_placeholder') }}">{{ old('footer_text', $appSettings['footer_text'] ?? '') }}</textarea>
              <div class="form-text">{{ __('ui.settings.footer_helper') }}</div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </main>
</div>
@endsection
