@extends('layouts.app')
@section('title','New Receipt')

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')
  <main>
    <div class="topbar">
      <button id="menuToggle" class="btn btn-soft rounded-circle p-2 d-lg-none"><i class="bi bi-list"></i></button>
      <h2 class="m-0">New Receipt</h2>
    </div>
    <div class="container-fluid py-3">
      @include('receipts._form')
    </div>
  </main>
</div>
@endsection
