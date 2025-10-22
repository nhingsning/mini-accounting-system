@extends('layouts.app')
@section('title','Quotations')

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')

  <main>
    {{-- Topbar --}}
    <div class="topbar">
      <button id="menuToggle" class="btn btn-soft rounded-circle p-2 d-lg-none" aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>
      <div class="search">
        <i class="bi bi-search"></i>
        <input class="form-control" placeholder="Search quotations…">
      </div>
      @if(Route::has('quotations.create'))
      <a href="{{ route('quotations.create') }}" class="btn btn-brand d-none d-sm-inline-flex">
        <i class="bi bi-plus-lg me-1"></i> New Quotation
      </a>
      @endif
    </div>

    <div class="container-fluid py-3">

      {{-- flash message --}}
      @if(session('ok'))
        <div class="alert alert-success d-flex align-items-center" role="alert">
          <i class="bi bi-check-circle me-2"></i> {{ session('ok') }}
        </div>
      @endif
      @if($errors->any())
        <div class="alert alert-danger" role="alert">
          <strong>เกิดข้อผิดพลาด:</strong>
          <ul class="mb-0 mt-1">
            @foreach($errors->all() as $err)
              <li>{{ $err }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="panel">
        <div class="panel-header">
          <strong>Quotations</strong>
          @if(Route::has('quotations.create'))
          <a href="{{ route('quotations.create') }}" class="btn btn-brand btn-sm">
            <i class="bi bi-plus-lg me-1"></i> New
          </a>
          @endif
        </div>

        <div class="panel-body">
          <div class="table-responsive">
            <table class="table align-middle">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>QT No.</th>
                  <th>Customer</th>
                  <th>Date</th>
                  <th class="text-end">Amount</th>
                  <th>Status</th>
                  <th class="text-end">Actions</th>
                </tr>
              </thead>
<tbody>
  @forelse($quotes as $q)
    @php
      // วันที่: ใช้ issue_date ก่อน ถ้าไม่มีค่อย fallback created_at
      $date = $q->issue_date ?: $q->created_at;

      // สถานะ
      $status = strtolower($q->status ?? 'draft');
      $badge  = $status==='approved' ? 'success' : ($status==='rejected' ? 'danger' : 'secondary');

      // เลขใบเสนอราคา: ใช้ $q->number ก่อน
      // ถ้าไม่มี ให้ประกอบจาก period/month_seq หรือจากวันที่ + id
      if (!empty($q->number)) {
        $qtNo = $q->number;
      } elseif (!empty($q->period) && !empty($q->month_seq)) {
        $qtNo = 'QT'.$q->period.'-'.str_pad($q->month_seq, 4, '0', STR_PAD_LEFT);
      } else {
        $period = \Carbon\Carbon::parse($date)->format('Y-m');
        $qtNo   = 'QT'.$period.'-'.str_pad($q->id, 4, '0', STR_PAD_LEFT);
      }
    @endphp

    <tr>
      <td>{{ $q->id }}</td>

      {{-- QT No. ลิงก์เข้า view --}}
      <td>
        <a href="{{ route('quotations.show', $q) }}" class="text-decoration-none">
          {{ $qtNo }}
        </a>
      </td>

      <td>{{ $q->customer_name ?? '—' }}</td>
      <td>{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</td>

      <td class="text-end">
        {{ config('currency.symbol','฿') }}{{ number_format($q->total ?? 0, 2) }}
      </td>

      <td>
        <span class="badge text-bg-{{ $badge }}">{{ ucfirst($status) }}</span>
      </td>

      <td class="text-end" style="white-space:nowrap">
        @if(Route::has('quotations.show'))
          <a href="{{ route('quotations.show', $q) }}" class="btn btn-sm btn-light" title="View">
            <i class="bi bi-eye"></i>
          </a>
        @endif
        @if(Route::has('quotations.edit'))
          <a href="{{ route('quotations.edit', $q) }}" class="btn btn-sm btn-light" title="Edit">
            <i class="bi bi-pencil"></i>
          </a>
        @endif
        @if(Route::has('quotations.destroy'))
          <form action="{{ route('quotations.destroy', $q) }}" method="POST" class="d-inline"
                onsubmit="return confirm('ลบใบเสนอราคา {{ $qtNo }} ของลูกค้า {{ $q->customer_name }} ?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
              <i class="bi bi-trash"></i>
            </button>
          </form>
        @endif
      </td>
    </tr>
  @empty
    <tr>
      <td colspan="7" class="text-center text-muted py-4">No quotations yet.</td>
    </tr>
  @endforelse
</tbody>

            </table>
          </div>

          <div class="mt-3">
            {{ $quotes->withQueryString()->links() }}
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
@endsection
