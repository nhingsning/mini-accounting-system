@extends('layouts.app')

@section('content')
<h1 class="text-3xl font-bold mb-6">Edit Invoice {{ $invoice->number }}</h1>

<form method="post" action="{{ route('quotes.update', $invoice) }}" id="invoice-form" class="grid lg:grid-cols-3 gap-6">
  @csrf
  @method('PUT')

  <div class="lg:col-span-2 space-y-4">
    <div class="card">
      <label class="block mb-2 font-semibold">Customer Name</label>
      <input name="customer_name" class="input w-full" required value="{{ old('customer_name', $invoice->customer_name) }}">
    </div>

    <div class="grid grid-cols-3 gap-4 card">
      <div>
        <label class="block mb-2 font-semibold">Issue Date</label>
        <input type="date" name="issue_date" class="input w-full" required value="{{ old('issue_date', \Illuminate\Support\Carbon::parse($invoice->issue_date)->toDateString()) }}">
      </div>
      <div>
        <label class="block mb-2 font-semibold">Due Date</label>
        <input type="date" name="due_date" class="input w-full" value="{{ old('due_date', optional($invoice->due_date)->toDateString()) }}">
      </div>
      <div>
        <label class="block mb-2 font-semibold">Tax Rate (%)</label>
        <input type="number" step="0.01" name="tax_rate" class="input w-full" value="{{ old('tax_rate', $invoice->tax_rate) }}">
      </div>
    </div>

    <div class="card overflow-x-auto">
      <table class="table" id="items-table">
        <thead class="border-b bg-gray-50">
          <tr>
            <th>Description</th>
            <th>Qty</th>
            <th>Unit</th>
            <th>Price</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="items-body">
          @foreach($invoice->items as $item)
          <tr>
            <td><input name="items[][description]" class="input w-full" value="{{ $item->description }}"></td>
            <td><input name="items[][qty]" type="number" class="input w-20 text-right" value="{{ $item->qty }}"></td>
            <td><input name="items[][unit]" class="input w-20" value="{{ $item->unit }}"></td>
            <td><input name="items[][price]" type="number" step="0.01" class="input w-24 text-right" value="{{ $item->price }}"></td>
            <td><button type="button" class="btn btn-ghost text-red-500 remove-row">×</button></td>
          </tr>
          @endforeach
        </tbody>
      </table>
      <button type="button" id="add-row" class="btn btn-secondary mt-2">+ Add Item</button>
    </div>
  </div>

  <div>
    <div class="card">
      <button type="submit" class="btn btn-primary w-full">Update Invoice</button>
      <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-ghost w-full mt-2">Cancel</a>
    </div>
  </div>
</form>

<script>
document.getElementById('add-row').addEventListener('click', () => {
  const tbody = document.getElementById('items-body');
  const row = document.createElement('tr');
  row.innerHTML = `
    <td><input name="items[][description]" class="input w-full"></td>
    <td><input name="items[][qty]" type="number" class="input w-20 text-right" value="1"></td>
    <td><input name="items[][unit]" class="input w-20"></td>
    <td><input name="items[][price]" type="number" step="0.01" class="input w-24 text-right" value="0"></td>
    <td><button type="button" class="btn btn-ghost text-red-500 remove-row">×</button></td>
  `;
  tbody.appendChild(row);
});

document.addEventListener('click', e => {
  if (e.target.classList.contains('remove-row')) {
    e.target.closest('tr').remove();
  }
});
</script>
@endsection
