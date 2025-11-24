@extends('layouts.app')
@section('title','Receipts')

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')
  <main>
    <div class="topbar">
      <button id="menuToggle" class="btn btn-soft rounded-circle p-2 d-lg-none"><i class="bi bi-list"></i></button>
      <h2 class="m-0">Receipts</h2>
      <div class="ms-auto"></div>
      <a href="{{ route('receipts.create') }}" class="btn btn-brand"><i class="bi bi-plus-lg"></i> New Receipt</a>
    </div>

    <div class="container-fluid py-3">
      <div class="panel">
        <div class="panel-body">
          @php
            $statusLabels = [
              'draft' => 'Draft',
              'issued' => 'Issued / Completed',
              'cancelled' => 'Cancelled / Void',
              'void' => 'Cancelled / Void',
            ];
          @endphp
          <form class="row g-2 align-items-center mb-3" method="GET" action="{{ route('receipts.index') }}">
            <div class="col-auto">
              <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="ค้นหาเลขที่ใบเสร็จหรือชื่อลูกค้า">
            </div>
            <div class="col-auto">
              <button class="btn btn-light" type="submit"><i class="bi bi-search"></i> Search</button>
            </div>
          </form>

          <div class="table-responsive">
            <table class="table align-middle">
              <thead class="table-light">
                <tr>
                  <th>Receipt</th>
                  <th>Invoice</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th class="text-end">Total</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($receipts as $receipt)
                  <tr>
                    <td class="fw-semibold">{{ $receipt->number ?? ('RC#'.$receipt->id) }}</td>
                    <td>{{ $receipt->invoice_number ?? ($receipt->invoice_id ? 'INV#'.$receipt->invoice_id : '—') }}</td>
                    <td>{{ $receipt->customer_name }}</td>
                    <td>{{ $receipt->issue_date?->format('Y-m-d') ?? '—' }}</td>
                    <td class="text-end">{{ number_format($receipt->total,2) }}</td>
                    <td><span class="badge text-bg-light text-uppercase">{{ $statusLabels[strtolower($receipt->status ?? 'draft')] ?? ucfirst($receipt->status ?? 'draft') }}</span></td>
                    <td class="text-end">
                      <a href="{{ route('receipts.show', $receipt->number ?? $receipt->id) }}" class="btn btn-sm btn-light">View</a>
                      <a href="{{ route('receipts.edit', $receipt->number ?? $receipt->id) }}" class="btn btn-sm btn-brand">Edit</a>
                      <form action="{{ route('receipts.destroy', $receipt->number ?? $receipt->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete receipt?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="7" class="text-center text-muted">No receipts yet.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="mt-3">{{ $receipts->links() }}</div>
        </div>
      </div>
    </div>
  </main>
</div>
@endsection
