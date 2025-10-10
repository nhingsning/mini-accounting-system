@extends('layouts.app')

@section('content')
<div class="flex items-center justify-between mb-6">
  <div>
    <h1 class="text-3xl font-bold">Invoice {{ $invoice->number }}</h1>
    <p class="text-sm text-gray-500">{{ $invoice->customer_name }}</p>
  </div>
  <div class="flex gap-2">
    <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-primary">Edit</a>
    <a href="{{ route('invoices.index') }}" class="btn btn-ghost">Back</a>
  </div>
</div>

@if (session('ok'))
  <div class="card mb-4 text-emerald-700 bg-emerald-50 border border-emerald-200">
    {{ session('ok') }}
  </div>
@endif

<div class="grid lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2 space-y-4">
    <div class="card">
      <div class="grid sm:grid-cols-3 gap-4">
        <div>
          <div class="text-sm text-gray-600">Issue date</div>
          <div class="font-medium">{{ \Illuminate\Support\Carbon::parse($invoice->issue_date)->format('d/m/Y') }}</div>
        </div>
        <div>
          <div class="text-sm text-gray-600">Due date</div>
          <div class="font-medium">
            {{ $invoice->due_date ? \Illuminate\Support\Carbon::parse($invoice->due_date)->format('d/m/Y') : '-' }}
          </div>
        </div>
        <div>
          <div class="text-sm text-gray-600">Tax rate</div>
          <div class="font-medium">{{ rtrim(rtrim(number_format($invoice->tax_rate,2), '0'), '.') }}%</div>
        </div>
      </div>
    </div>

    <div class="card overflow-x-auto">
      <table class="table">
        <thead class="border-b bg-gray-50">
          <tr>
            <th class="th w-12">#</th>
            <th class="th">Description</th>
            <th class="th w-28">Qty</th>
            <th class="th w-24">Unit</th>
            <th class="th w-40">Unit Price</th>
            <th class="th w-40">Total</th>
          </tr>
        </thead>
        <tbody>
          @foreach($invoice->items as $i => $it)
            <tr class="border-b last:border-0">
              <td class="td">{{ $i+1 }}</td>
              <td class="td">{{ $it->description }}</td>
              <td class="td">{{ $it->qty }}</td>
              <td class="td">{{ $it->unit ?? '-' }}</td>
              <td class="td text-right">{{ number_format($it->price, 2) }}</td>
              <td class="td text-right">{{ number_format($it->line_total, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <aside class="card space-y-2 h-fit">
    <div class="flex items-center justify-between">
      <span class="text-sm text-gray-600">Subtotal</span>
      <span class="font-semibold">{{ number_format($invoice->subtotal, 2) }}</span>
    </div>
    <div class="flex items-center justify-between">
      <span class="text-sm text-gray-600">Tax ({{ rtrim(rtrim(number_format($invoice->tax_rate,2), '0'), '.') }}%)</span>
      <span class="font-semibold">{{ number_format($invoice->tax, 2) }}</span>
    </div>
    <hr>
    <div class="flex items-center justify-between">
      <span class="text-base font-semibold">Grand Total</span>
      <span class="text-xl font-bold">{{ number_format($invoice->total, 2) }}</span>
    </div>
  </aside>
</div>
@endsection
