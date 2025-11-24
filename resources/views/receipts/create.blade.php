@extends('layouts.app')
@section('title','New Receipt')

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')
  <main>
    @include('receipts._form')
  </main>
</div>
@endsection
