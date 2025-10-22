@extends('layouts.app')
@section('title','Customer: '.$customer->name)

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')

  <main>
    <div class="topbar">
      <h2 class="m-0">Customer: {{ $customer->name }}</h2>
      <div class="ms-auto d-flex gap-2">
        <a class="btn btn-soft" href="{{ route('customers.edit',$customer) }}">Edit</a>
        <form action="{{ route('customers.destroy',$customer) }}" method="POST"
              onsubmit="return confirm('Delete this customer?');">
          @csrf @method('DELETE')
          <button class="btn btn-danger">Delete</button>
        </form>
      </div>
    </div>

    <div class="container-fluid py-3">
      <div class="panel">
        <div class="panel-header"><strong>Customer Data</strong></div>
        <div class="panel-body">
          <div class="row g-3">
            <div class="col-md-6"><div class="text-muted">Customer Name</div><div class="fw-600">{{ $customer->name }}</div></div>
            <div class="col-md-6"><div class="text-muted">Address</div><div>{{ nl2br(e($customer->address_show)) }}</div></div>

            @if($customer->name_private)
              <div class="col-md-6"><div class="text-muted">Customer Name (no show)</div><div>{{ $customer->name_private }}</div></div>
            @endif
            @if($customer->address_private)
              <div class="col-md-6"><div class="text-muted">Address (no show)</div><div>{{ nl2br(e($customer->address_private)) }}</div></div>
            @endif

            <div class="col-md-3"><div class="text-muted">Tax ID</div><div>{{ $customer->tax_id }}</div></div>
            <div class="col-md-3"><div class="text-muted">Tel</div><div>{{ $customer->tel }}</div></div>
            <div class="col-md-3"><div class="text-muted">Fax</div><div>{{ $customer->fax }}</div></div>
            <div class="col-md-3"><div class="text-muted">Payment Terms</div><div>{{ $customer->payment_terms }}</div></div>

            <div class="col-md-3"><div class="text-muted">Type</div>
              <div>{{ $customer->office_type === 'head' ? 'Head Office' : 'Branch' }}</div>
            </div>
            @if($customer->branch_code)
              <div class="col-md-3"><div class="text-muted">Branch Code</div><div>{{ $customer->branch_code }}</div></div>
            @endif
          </div>
        </div>
      </div>

      <div class="panel mt-3">
        <div class="panel-header"><strong>Contact Persons</strong></div>
        <div class="panel-body p-0">
          <table class="table m-0">
            <thead><tr>
              <th>Contact Name</th><th>Department</th><th>Position</th><th>Mobile</th><th>Email</th>
            </tr></thead>
            <tbody>
              @forelse($customer->contacts as $ct)
                <tr>
                  <td>{{ $ct->contact_name }}</td>
                  <td>{{ $ct->department }}</td>
                  <td>{{ $ct->position }}</td>
                  <td>{{ $ct->mobile }}</td>
                  <td>{{ $ct->email }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-muted text-center py-3">No contacts</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="mt-3">
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Back</a>
        <a href="{{ route('customers.edit',$customer) }}" class="btn btn-primary">Edit</a>
      </div>
    </div>
  </main>
</div>
@endsection
