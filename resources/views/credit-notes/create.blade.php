@extends('layouts.app')

@section('title', __('ui.credit_notes.title'))

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')

  <main>
    <div class="topbar">
      <button id="menuToggle" class="btn btn-soft rounded-circle p-2 d-lg-none" aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>
      <h2 class="m-0">{{ __('ui.credit_notes.title') }}</h2>
    </div>

    <div class="container-fluid py-3">
      @include('credit-notes._form')
    </div>
  </main>
</div>
@endsection
