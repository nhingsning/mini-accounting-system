@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
  <h1 class="text-3xl font-bold">Quotations</h1>
  <a href="{{ route('quotes.create') }}" class="btn btn-primary">+ New Quote</a>
</div>

@if(session('ok'))
  <div class="card mb-4 text-emerald-700">{{ session('ok') }}</div>
@endif

@if($quotes->isEmpty())
  <div class="card text-gray-500">ยังไม่มีข้อมูล</div>
@else
  <div class="overflow-x-auto card">
    <table class="table">
      <thead>
        <tr>
          <th class="th">Number</th>
          <th class="th">Customer</th>
          <th class="th">Issue Date</th>
          <th class="th text-right">Total</th>
          <th class="th">Status</th>
          <th class="th w-40"></th>
        </tr>
      </thead>
      <tbody>
        @foreach($quotes as $q)
          <tr class="border-b">
            <td class="td font-medium">{{ $q->number }}</td>
            <td class="td">{{ $q->customer_name }}</td>
            <td class="td">{{ optional($q->issue_date)->format('d/m/Y') }}</td>
            <td class="td text-right">{{ number_format($q->total,2) }}</td>
            <td class="td">{{ $q->status }}</td>
            <td class="td">
              <a class="btn btn-ghost btn-sm" href="{{ route('quotes.edit',$q) }}">Edit</a>
              <a class="btn btn-ghost btn-sm" href="{{ route('quotes.show',$q) }}">View</a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <div class="mt-4">{{ $quotes->links() }}</div>
@endif
@endsection
