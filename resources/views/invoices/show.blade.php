@extends('layouts.app')

@section('content')
<h1>Invoice {{ $invoice->number }}</h1>
<p>Customer: {{ $invoice->customer_name }} |
   Date: {{ $invoice->issue_date }}</p>

<table class="mt">
  <thead>
    <tr>
      <th>Description</th>
      <th class="right">Qty</th>
      <th class="right">Price</th>
      <th class="right">Line</th>
    </tr>
  </thead>
  <tbody>
  @foreach($invoice->items as $it)
    <tr>
      <td>{{ $it->description }}</td>
      <td class="right">{{ $it->qty }}</td>
      <td class="right">{{ number_format($it->price,2) }}</td>
      <td class="right">{{ number_format($it->line_total,2) }}</td>
    </tr>
  @endforeach
  </tbody>
</table>

<p class="right mt">Subtotal: {{ number_format($invoice->subtotal,2) }}</p>
<p class="right">Tax: {{ number_format($invoice->tax,2) }}</p>
<p class="right"><strong>Total: {{ number_format($invoice->total,2) }}</strong></p>

<p class="mt"><a class="btn" href="{{ route('invoices.index') }}">Back</a></p>
@endsection
