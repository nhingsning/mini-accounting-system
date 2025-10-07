@extends('layouts.app')

@section('content')
<h1>New Invoice</h1>

<form class="mt" method="post" action="{{ route('invoices.store') }}">
  @csrf
  <p>
    <label>Customer Name<br>
      <input type="text" name="customer_name" value="{{ old('customer_name') }}" required>
    </label>
  </p>
  <p>
    <label>Issue date<br>
      <input type="date" name="issue_date" value="{{ old('issue_date', now()->toDateString()) }}" required>
    </label>
  </p>
  <p>
    <label>Due date<br>
      <input type="date" name="due_date" value="{{ old('due_date') }}">
    </label>
  </p>
  <p>
    <label>Tax rate (%)<br>
      <input type="number" name="tax_rate" step="0.01" value="{{ old('tax_rate',7) }}">
    </label>
  </p>

  <h3>Items</h3>
  <div id="items">
    <div class="item">
      <input type="text" name="items[0][description]" placeholder="Description" required>
      <input type="number" name="items[0][qty]" placeholder="Qty" value="1" min="1" required>
      <input type="number" step="0.01" name="items[0][price]" placeholder="Price" value="0" required>
    </div>
  </div>
  <p class="mt">
    <button type="button" class="btn" onclick="addItem()">+ Add item</button>
    <button type="submit" class="btn">Save</button>
    <a class="btn" href="{{ route('invoices.index') }}">Cancel</a>
  </p>
</form>

@if($errors->any())
  <div class="mt" style="background:#ffecec;border:1px solid #f5c2c7;padding:10px">
    <strong>มีข้อผิดพลาด:</strong>
    <ul>
      @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
    </ul>
  </div>
@endif

<script>
let i = 1;
function addItem(){
  const wrap = document.getElementById('items');
  const div = document.createElement('div');
  div.className = 'item';
  div.innerHTML = `
    <input type="text" name="items[${i}][description]" placeholder="Description" required>
    <input type="number" name="items[${i}][qty]" placeholder="Qty" value="1" min="1" required>
    <input type="number" step="0.01" name="items[${i}][price]" placeholder="Price" value="0" required>
  `;
  wrap.appendChild(div);
  i++;
}
</script>
@endsection
