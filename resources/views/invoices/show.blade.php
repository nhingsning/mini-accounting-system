@extends('layouts.app')
@section('title', ($invoice->number ?? ('Invoice #'.$invoice->id)))

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')

  <main>
    <div class="topbar">
      <button id="menuToggle" class="btn btn-soft rounded-circle p-2 d-lg-none" aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>
      <h2 class="m-0">
        {{ $invoice->number ?? ('Invoice #'.$invoice->id) }}
        <span class="badge ms-2
          {{ (strtolower($invoice->status ?? '')==='paid') ? 'text-bg-success' :
             ((strtolower($invoice->status ?? '')==='cancelled') ? 'text-bg-danger' : 'text-bg-warning') }}">
          {{ ucfirst($invoice->status ?? 'Unpaid') }}
        </span>
      </h2>
      <div class="ms-auto d-flex gap-2">
        <a href="{{ route('invoices.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
        @if(Route::has('invoices.edit'))
          <a href="{{ route('invoices.edit',$invoice->id) }}" class="btn btn-brand"><i class="bi bi-pencil"></i> Edit</a>
        @endif
        <button class="btn btn-light" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
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
                  <div class="fw-semibold">{{ $invoice->customer_name ?? '—' }}</div>
                </div>
                <div class="col-md-3">
                  <div class="mini">Issue Date</div>
                  <div class="fw-semibold">
                    {{ \Carbon\Carbon::parse($invoice->issue_date ?? $invoice->created_at)->format('M d, Y') }}
                  </div>
                </div>
                <div class="col-md-3">
                  <div class="mini">Due Date</div>
                  <div class="fw-semibold">
                    {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') : '—' }}
                  </div>
                </div>
              </div>

              <hr class="my-4">

              <div class="table-responsive">
                <table class="table align-middle">
                  <thead class="table-light">
                    <tr>
                      <th style="width:50%">Description</th>
                      <th class="text-end" style="width:10%">Qty</th>
                      <th class="text-end" style="width:20%">Unit Price</th>
                      <th class="text-end" style="width:20%">Line Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse(($invoice->items ?? []) as $it)
                      <tr>
                        <td>{{ $it->description }}</td>
                        <td class="text-end">{{ number_format($it->qty ?? 0, 2) }}</td>
                        <td class="text-end">${{ number_format(($it->price ?? $it->unit_price ?? 0), 2) }}</td>
                        <td class="text-end">${{ number_format(($it->line_total ?? (($it->qty ?? 0)*($it->price ?? $it->unit_price ?? 0))), 2) }}</td>
                      </tr>
                    @empty
                      <tr><td colspan="4" class="text-center text-muted">No items.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        {{-- Summary --}}
        <div class="col-12 col-xl-4">
          <div class="panel">
            <div class="panel-header"><strong>Summary</strong></div>
            <div class="panel-body">
              @php
                $subtotal  = round($invoice->subtotal ?? ($invoice->items? $invoice->items->sum('line_total'):0), 2);
                $discRate  = (float)($invoice->discount_rate ?? 0);
                $taxRate   = (float)($invoice->tax_rate ?? 0);
                $taxValue  = round($invoice->tax ?? ($subtotal*(1-($discRate/100))*($taxRate/100)), 2);
                $total     = round($invoice->total ?? ($subtotal*(1-($discRate/100)) + $taxValue), 2);
              @endphp

              <div class="d-flex justify-content-between py-1">
                <span>Subtotal</span><span class="fw-semibold">${{ number_format($subtotal,2) }}</span>
              </div>
              <div class="d-flex justify-content-between py-1">
                <span>Discount</span>
                <span class="fw-semibold">{{ rtrim(rtrim(number_format($discRate,2), '0'),'.') }}%</span>
              </div>
              <div class="d-flex justify-content-between py-1">
                <span>Tax ({{ rtrim(rtrim(number_format($taxRate,2), '0'),'.') }}%)</span>
                <span class="fw-semibold">${{ number_format($taxValue,2) }}</span>
              </div>
              <hr>
              <div class="d-flex justify-content-between py-1 fs-5">
                <span>Total</span><span class="fw-bold">${{ number_format($total,2) }}</span>
              </div>
            </div>
          </div>

          <div class="mini mt-2">Last updated: {{ $invoice->updated_at?->format('M d, Y H:i') }}</div>
        </div>
      </div>
    </div>
  </main>
</div>
@endsection
