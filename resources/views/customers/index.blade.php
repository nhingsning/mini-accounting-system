@extends('layouts.app')
@section('title','Customers')

@section('body')
<div class="layout">
  {{-- Sidebar เดียวกับหน้าอื่น --}}
  @includeIf('partials.sidebar')

  <main>
    {{-- Topbar --}}
    <div class="topbar">
      <h2 class="m-0">Customer Master</h2>
      <a href="{{ route('customers.create') }}" class="btn btn-primary ms-auto">+ New</a>
    </div>

    <div class="container-fluid py-3">
      {{-- Flash message --}}
      @if(session('ok'))
        <div class="alert alert-success">{{ session('ok') }}</div>
      @endif

      {{-- ค้นหาเล็กๆ (ถ้าไม่ใช้ลบฟอร์มนี้ได้) --}}
      <form class="row g-2 mb-3" method="get">
        <div class="col-md-4">
          <input name="search" value="{{ request('search') }}" class="form-control" placeholder="Search name / tax id / tel">
        </div>
        <div class="col">
          <button class="btn btn-outline-secondary">Search</button>
        </div>
      </form>

      <div class="panel">
        <div class="panel-body p-0">
          <table class="table table-hover align-middle m-0">
            <thead>
              <tr>
                <th>Name</th>
                <th>Tax ID</th>
                <th>Tel</th>
                <th>Payment Terms</th>
                <th>Type</th>
                <th width="120" class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @forelse($customers as $c)
                <tr>
                  <td class="fw-600">{{ $c->name }}</td>
                  <td>{{ $c->tax_id }}</td>
                  <td>{{ $c->tel }}</td>
                  <td>{{ $c->payment_terms }}</td>
                  <td>{{ $c->office_type === 'head' ? 'Head' : 'Branch' }}@if($c->branch_code) ({{ $c->branch_code }})@endif</td>
<td class="text-end">
  <div class="d-inline-flex gap-2">
    {{-- View --}}
    <a href="{{ route('customers.show',$c) }}"
       class="btn btn-sm btn-light" title="View">
      <i class="bi bi-eye"></i>
      <span class="visually-hidden">View</span>
    </a>

    {{-- Edit --}}
    <a href="{{ route('customers.edit',$c) }}"
       class="btn btn-sm btn-light" title="Edit">
      <i class="bi bi-pencil"></i>
      <span class="visually-hidden">Edit</span>
    </a>

    {{-- Delete --}}
    <form action="{{ route('customers.destroy',$c) }}" method="POST" class="d-inline"
          onsubmit="return confirm('Delete this customer?');">
      @csrf @method('DELETE')
      <button class="btn btn-sm btn-danger" title="Delete" type="submit">
        <i class="bi bi-trash"></i>
      </button>
    </form>
  </div>
</td>



                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No customers yet</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="panel-footer">
          {{ $customers->links() }}
        </div>
      </div>
    </div>
  </main>
</div>
@endsection
