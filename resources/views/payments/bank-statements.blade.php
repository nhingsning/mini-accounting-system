@extends('layouts.app')
@section('title', __('ui.payments.bank_title'))

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h4 mb-0">{{ __('ui.payments.bank_title') }}</h1>
      <div class="text-muted small">{{ __('ui.payments.helpers.bank_subtitle') }}</div>
    </div>
    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">{{ __('ui.payments.actions.back_payments') }}</a>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-md-7">
          <form action="{{ route('bank-statements.import') }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
            @csrf
            <div class="col-12 col-md-8">
              <label class="form-label">{{ __('ui.payments.fields.bank_file') }}</label>
              <input type="file" name="statement_file" class="form-control" accept=".csv,text/csv" required>
            </div>
            <div class="col-12 col-md-4">
              <button class="btn btn-primary w-100" type="submit">{{ __('ui.payments.actions.import_bank') }}</button>
            </div>
          </form>
        </div>
        <div class="col-md-5 d-flex justify-content-md-end">
          <form action="{{ route('bank-statements.reconcile') }}" method="POST" class="w-100 w-md-auto">
            @csrf
            <button class="btn btn-success w-100" type="submit">{{ __('ui.payments.actions.reconcile') }}</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="card-title fw-bold">{{ __('ui.payments.bank_table.title') }}</h5>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead class="table-light">
            <tr>
              <th>{{ __('ui.payments.bank_table.date') }}</th>
              <th>{{ __('ui.payments.bank_table.description') }}</th>
              <th>{{ __('ui.payments.bank_table.reference') }}</th>
              <th class="text-end">{{ __('ui.payments.bank_table.amount') }}</th>
              <th>{{ __('ui.payments.bank_table.status') }}</th>
            </tr>
          </thead>
          <tbody>
            @forelse($statements as $line)
              <tr>
                <td>{{ optional($line->transaction_date)->format('Y-m-d') ?: '—' }}</td>
                <td>{{ $line->description ?: '—' }}</td>
                <td>{{ $line->reference ?: '—' }}</td>
                <td class="text-end fw-bold">{{ number_format($line->amount,2) }}</td>
                <td>
                  <span class="badge {{ $line->status === 'matched' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' }}">
                    {{ ucfirst($line->status) }}
                  </span>
                  @if($line->invoice)
                    <div class="text-muted small">{{ $line->invoice->number }}</div>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted py-4">{{ __('ui.payments.bank_table.empty') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
