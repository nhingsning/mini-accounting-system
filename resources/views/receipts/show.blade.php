@extends('layouts.app')
@section('title', ($receipt->number ?? ('Receipt #'.$receipt->id)))

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')

  <main>
    <div class="topbar">
      <button id="menuToggle" class="btn btn-soft rounded-circle p-2 d-lg-none" aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>
      <h2 class="m-0">
        {{ $receipt->number ?? ('Receipt #'.$receipt->id) }}
        <span class="badge text-bg-primary ms-2">{{ ucfirst($receipt->status ?? 'draft') }}</span>
        @if($receipt->invoice_number || $receipt->invoice_id)
          <span class="badge text-bg-secondary ms-2">Invoice {{ $receipt->invoice_number ?? ('#'.$receipt->invoice_id) }}</span>
        @endif
      </h2>
      <div class="ms-auto d-flex gap-2">
        <a href="{{ route('receipts.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
        @if(Route::has('receipts.edit'))
          <a href="{{ route('receipts.edit',$receipt->number ?? $receipt->id) }}" class="btn btn-brand"><i class="bi bi-pencil"></i> Edit</a>
        @endif
      </div>
    </div>

    <div class="container-fluid py-3">
      <div class="row g-3">
        <div class="col-12 col-xl-8">
          <div class="panel">
            <div class="panel-header"><strong>Bill To</strong></div>
            <div class="panel-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="mini">Customer</div>
                  <div class="fw-semibold">{{ $receipt->customer_name ?? '—' }}</div>
                </div>
                <div class="col-md-3">
                  <div class="mini">Issue Date</div>
                  <div class="fw-semibold">{{ optional($receipt->issue_date ?? $receipt->created_at)->format('M d, Y') }}</div>
                </div>
                <div class="col-md-3">
                  <div class="mini">Invoice</div>
                  @if($receipt->invoice_number)
                    <div class="fw-semibold">{{ $receipt->invoice_number }}</div>
                  @elseif($receipt->invoice_id)
                    <div class="fw-semibold">INV#{{ $receipt->invoice_id }}</div>
                  @else
                    <div class="text-muted">—</div>
                  @endif
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-md-8">
                  <div class="mini">Address</div>
                  <div>{{ $receipt->customer_address ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                  <div class="mini">Tax ID</div>
                  <div>{{ $receipt->customer_tax_id ?? '—' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-4">
          <div class="panel">
            <div class="panel-header"><strong>Summary</strong></div>
            <div class="panel-body">
              <div class="d-flex justify-content-between py-1">
                <span>Amount</span>
                <span class="fw-semibold">{{ number_format($receipt->total ?? 0, 2) }}</span>
              </div>
              <hr>
              <div class="d-flex justify-content-between py-1 fs-5">
                <span>Total</span><span class="fw-bold">{{ number_format($receipt->total ?? 0, 2) }}</span>
              </div>
            </div>
          </div>
          <div class="mini mt-2">Last updated: {{ $receipt->updated_at?->format('M d, Y H:i') }}</div>
        </div>
      </div>
    </div>
  </main>
</div>
@endsection
