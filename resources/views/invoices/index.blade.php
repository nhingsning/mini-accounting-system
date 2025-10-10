@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h1 class="text-3xl font-bold">Invoices</h1>
  <div class="flex gap-2">
    <a href="{{ route('quotes.index') }}" class="btn btn-ghost">Quotations</a>
    <a href="{{ route('invoices.create') }}" class="btn btn-primary">+ New Invoice</a>
  </div>
</div>

@if(session('ok'))
  <div class="card mb-4 text-emerald-700 bg-emerald-50 border border-emerald-200">
    {{ session('ok') }}
  </div>
@endif

@if($invoices->isEmpty())
  <div class="text-gray-500">ยังไม่มีข้อมูล</div>
@else
  <div class="card overflow-x-auto">
    <table class="table">
      <thead class="border-b bg-gray-50">
        <tr>
          <th class="th">Number</th>
          <th class="th">Customer</th>
          <th class="th">Issue date</th>
          <th class="th text-right">Total</th>
          <th class="th w-40"></th>
        </tr>
      </thead>
      <tbody>
      @foreach($invoices as $inv)
        <tr class="border-b last:border-0">
          <td class="td font-medium">{{ $inv->number }}</td>
          <td class="td">{{ $inv->customer_name }}</td>
          <td class="td">{{ \Illuminate\Support\Carbon::parse($inv->issue_date)->format('d/m/Y') }}</td>
          <td class="td text-right">{{ number_format($inv->total, 2) }}</td>
          <td class="td text-right">
            <a href="{{ route('invoices.show', $inv) }}" class="btn btn-ghost">View</a>
            <a href="{{ route('invoices.edit', $inv) }}" class="btn btn-ghost">Edit</a>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $invoices->links() }}</div>
@endif
@endsection
