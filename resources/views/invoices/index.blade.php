@extends('layouts.app')

@section('content')
<h1>Invoices</h1>
<p><a href="https://fictional-invention-rp57rw7xjpqhx6r7-8080.app.github.dev/invoices/create" class="btn">+ New Invoice</a>

</p>

@if($invoices->count()==0)
  <p>ยังไม่มีข้อมูล</p>
@else
<table class="mt">
  <thead>
    <tr>
      <th>No.</th>
      <th>Customer</th>
      <th class="right">Total</th>
      <th>Status</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
  @foreach($invoices as $inv)
    <tr>
      <td>{{ $inv->number }}</td>
      <td>{{ $inv->customer_name }}</td>
      <td class="right">{{ number_format($inv->total,2) }}</td>
      <td>{{ $inv->status }}</td>
      <td><a class="btn" href="{{ route('invoices.show',$inv) }}">View</a></td>
    </tr>
  @endforeach
  </tbody>
</table>
{{ $invoices->links() }}
@endif
@endsection
