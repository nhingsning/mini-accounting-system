@extends('layouts.app')
@section('title', __('ui.payments.title'))

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')

  <main>
    {{-- Topbar --}} 
    <div class="topbar">
      <button id="menuToggle" class="btn btn-soft rounded-circle p-2 d-lg-none" aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>
      <h1 class="h5 mb-0">{{ __('ui.payments.title') }}</h1>
      <div class="ms-auto"></div>
      <a href="{{ route('bank-statements.index') }}" class="btn btn-outline-ghost d-none d-md-inline-flex">
        <i class="bi bi-bank me-1"></i> {{ __('ui.payments.bank_nav') }}
      </a>
    </div>

    {{-- Content --}} 
    <div class="container-fluid py-3">
      <div class="row g-3">
        <div class="col-lg-6">
          <div class="panel h-100">
            <div class="panel-header">
              <div>
                <strong>{{ __('ui.payments.record_payment') }}</strong>
                <div class="mini">{{ __('ui.payments.helpers.record') }}</div>
              </div>
              <a href="{{ route('bank-statements.index') }}" class="btn btn-outline-ghost btn-sm d-md-none">
                <i class="bi bi-bank me-1"></i> {{ __('ui.payments.bank_nav') }}
              </a>
            </div>
            <div class="panel-body">
              <form action="{{ route('payments.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                @csrf
                <div class="col-12">
                  <label class="form-label">{{ __('ui.payments.fields.invoice') }}</label>
                  <select name="invoice_id" class="form-select" required>
                    <option value="">{{ __('ui.payments.fields.select_invoice') }}</option>
                    @foreach($invoices as $inv)
                      <option value="{{ $inv->id }}">{{ $inv->number ?? ('INV#'.$inv->id) }} — {{ $inv->customer_name }} ({{ number_format($inv->total,2) }})</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">{{ __('ui.payments.fields.amount') }}</label>
                  <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">{{ __('ui.payments.fields.method') }}</label>
                  <select name="method" class="form-select" required>
                    <option value="bank_transfer">{{ __('ui.payments.methods.bank_transfer') }}</option>
                    <option value="cash">{{ __('ui.payments.methods.cash') }}</option>
                    <option value="card">{{ __('ui.payments.methods.card') }}</option>
                    <option value="e_wallet">{{ __('ui.payments.methods.e_wallet') }}</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">{{ __('ui.payments.fields.paid_at') }}</label>
                  <input type="date" name="paid_at" class="form-control" value="{{ now()->toDateString() }}">
                </div>
                <div class="col-md-6">
                  <label class="form-label">{{ __('ui.payments.fields.reference') }}</label>
                  <input type="text" name="reference" class="form-control" placeholder="TRX / Ref ID">
                </div>
                <div class="col-md-6">
                  <label class="form-label">{{ __('ui.payments.fields.slip') }}</label>
                  <input type="file" name="slip" class="form-control" accept="image/*,application/pdf">
                </div>
                <div class="col-12">
                  <label class="form-label">{{ __('ui.payments.fields.note') }}</label>
                  <textarea name="note" rows="2" class="form-control" placeholder="{{ __('ui.payments.helpers.note') }}"></textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                  <button class="btn btn-brand" type="submit">{{ __('ui.payments.actions.save_payment') }}</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="panel h-100">
            <div class="panel-header d-flex justify-content-between align-items-center">
              <div>
                <strong>{{ __('ui.payments.recent') }}</strong>
                <div class="mini text-muted">{{ __('ui.payments.helpers.recent_count', ['count' => $payments->count()]) }}</div>
              </div>
              <span class="badge bg-light text-dark">{{ $payments->count() }}</span>
            </div>
            <div class="panel-body">
              <div class="table-responsive" style="max-height:380px;">
                <table class="table align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>{{ __('ui.payments.fields.invoice') }}</th>
                      <th>{{ __('ui.payments.fields.method') }}</th>
                      <th class="text-end">{{ __('ui.payments.fields.amount') }}</th>
                      <th>{{ __('ui.payments.fields.status') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($payments as $payment)
                      <tr>
                        <td>
                          <div class="fw-semibold">{{ $payment->invoice?->number ?? 'INV#'.$payment->invoice_id }}</div>
                          <div class="text-muted small">{{ optional($payment->paid_at)->format('Y-m-d') }}</div>
                        </td>
                        <td class="text-muted text-uppercase small">{{ str_replace('_',' ', $payment->method) }}</td>
                        <td class="text-end fw-bold">{{ number_format($payment->amount,2) }}</td>
                        <td>
                          <span class="badge bg-primary-subtle text-primary">{{ ucfirst($payment->status) }}</span>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="4" class="text-center text-muted py-4">{{ __('ui.payments.empty') }}</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
@endsection
