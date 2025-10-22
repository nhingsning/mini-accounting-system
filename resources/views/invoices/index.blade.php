@extends('layouts.app')
@section('title','Invoices')

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')

  <main>
    {{-- ===== Topbar ===== --}}
    <div class="topbar">
      <button id="menuToggle" class="btn btn-soft rounded-circle p-2 d-lg-none" aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>

      {{-- ===== Search form (GET /invoices?q=...) ===== --}}
      <form
        action="{{ Route::has('invoices.index') ? route('invoices.index') : url('/invoices') }}"
        method="GET"
        class="search"
        role="search"
        aria-label="Search invoices"
      >
        <i class="bi bi-search"></i>
        <input
          class="form-control"
          type="search"
          name="q"
          value="{{ old('q', $q ?? request('q')) }}"
          placeholder="Search invoices…"
          autocomplete="off"
        >
        <button type="submit" class="visually-hidden">Search</button>
      </form>

      <a href="{{ Route::has('invoices.create') ? route('invoices.create') : url('/invoices/create') }}"
         class="btn btn-brand d-none d-sm-inline-flex ms-auto">
        <i class="bi bi-plus-lg me-1"></i> New Invoice
      </a>
    </div>

    {{-- ===== Content ===== --}}
    <div class="container-fluid py-3">

      @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif
      @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
      @endif

      <div class="panel">
        <div class="panel-header d-flex justify-content-between align-items-center">
          <div>
            <strong>Invoices</strong>
            @if(($q ?? null) !== null && $q !== '')
              <span class="text-muted">— search: “{{ $q }}”</span>
            @endif>
          </div>
          <div class="d-flex align-items-center gap-3">
            <span class="text-muted small">Total: {{ $invoices->total() }}</span>
            <a href="{{ Route::has('invoices.create') ? route('invoices.create') : url('/invoices/create') }}"
               class="btn btn-brand btn-sm">
              <i class="bi bi-plus-lg me-1"></i> New
            </a>
          </div>
        </div>

        <div class="panel-body">
          <div class="table-responsive">
            <table class="table align-middle">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th class="text-end">Amount</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                @forelse($invoices as $inv)
                  @php
                    // วันที่: ใช้ issue_date ถ้ามี ไม่งั้น fallback created_at
                    $rawDate = $inv->issue_date ?? $inv->created_at;
                    try {
                      $dateStr = $rawDate ? \Carbon\Carbon::parse($rawDate)->format('M d, Y') : '—';
                    } catch (\Throwable $e) {
                      $dateStr = '—';
                    }

                    // สถานะ + สี badge
                    $status = strtolower($inv->status ?? 'unpaid');
                    $badge = match ($status) {
                      'paid'      => 'success',
                      'cancelled' => 'secondary',
                      'void'      => 'secondary',
                      'overdue'   => 'danger',
                      'draft'     => 'secondary',
                      'pending'   => 'warning',
                      default     => 'warning', // unpaid/others
                    };
                    $label = $status ? ucfirst($status) : 'Unpaid';
                  @endphp

                  <tr>
                    <td class="fw-medium">{{ $inv->number ?? $inv->id }}</td>
                    <td>{{ $inv->customer_name ?? '—' }}</td>
                    <td>{{ $dateStr }}</td>
                    <td class="text-end">
                      {{ isset($inv->total) ? '฿'.number_format((float)$inv->total, 2) : '—' }}
                    </td>
                    <td>
                      <span class="badge text-bg-{{ $badge }}">{{ $label }}</span>
                    </td>
                    <td class="text-end">
                      @if(Route::has('invoices.show'))
                        <a href="{{ route('invoices.show', $inv) }}" class="btn btn-sm btn-light" title="View">
                          <i class="bi bi-eye"></i>
                        </a>
                      @endif

                      @if(Route::has('invoices.edit'))
                        <a href="{{ route('invoices.edit', $inv) }}" class="btn btn-sm btn-light" title="Edit">
                          <i class="bi bi-pencil"></i>
                        </a>
                      @endif

                      @if(Route::has('invoices.destroy'))
                        <form action="{{ route('invoices.destroy', $inv) }}"
                              method="POST"
                              class="d-inline"
                              onsubmit="return confirm('แน่ใจหรือไม่ว่าต้องการลบใบแจ้งหนี้นี้?')">
                          @csrf
                          @method('DELETE')
                          <button class="btn btn-sm btn-danger" title="Delete" type="submit">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      @endif
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center text-muted py-5">
                      <div class="mb-2">No invoices{{ ($q ?? '') ? ' matched your search.' : ' yet.' }}</div>
                      <a class="btn btn-primary"
                         href="{{ Route::has('invoices.create') ? route('invoices.create') : url('/invoices/create') }}">
                        <i class="bi bi-plus-lg me-1"></i> Create your first invoice
                      </a>
                    </td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="mt-3">
            {{ $invoices->withQueryString()->links() }}
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
@endsection
