@extends('layouts.app')

@section('content')
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-3xl font-bold">Invoices</h1>
<a href="{{ route('invoices.create', [], false) }}" class="btn btn-primary">+ New Invoice</a>
  </div>

  @if($invoices->isEmpty())
    <div class="card text-gray-600">ยังไม่มีข้อมูล</div>
  @else
    {{-- ตารางรายการตามเดิมของหนิงได้เลย --}}
  @endif
@endsection
