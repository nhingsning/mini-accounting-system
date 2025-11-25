@extends('layouts.app')
@section('title', __('ui.payments.bank_title'))

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')

  <main>
    <div class="topbar">
      <button id="menuToggle" class="btn btn-soft rounded-circle p-2 d-lg-none" aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>
      <div>
        <div class="h5 mb-0">{{ __('ui.payments.bank_title') }}</div>
        <div class="mini">{{ __('ui.payments.helpers.bank_subtitle') }}</div>
      </div>
      <div class="ms-auto"></div>
      <a href="{{ route('payments.index') }}" class="btn btn-outline-ghost">
        <i class="bi bi-arrow-left me-1"></i> {{ __('ui.payments.actions.back_payments') }}
      </a>
    </div>

    <div class="container-fluid py-3">
      <div class="panel mb-3">
        <div class="panel-body">
          <div class="row g-3 align-items-end">
            <div class="col-md-7">
              <form action="{{ route('bank-statements.import') }}" method="POST" enctype="multipart/form-data" class="row g-2 align-items-end">
                @csrf
                <div class="col-12 col-md-8">
                  <label class="form-label">{{ __('ui.payments.fields.bank_file') }}</label>
                  <input type="file" name="statement_file" class="form-control" accept=".csv,text/csv" required>
                </div>
                <div class="col-12 col-md-4">
                  <button class="btn btn-brand w-100" type="submit">{{ __('ui.payments.actions.import_bank') }}</button>
                </div>
              </form>
            </div>
            <div class="col-md-5 d-flex justify-content-md-end">
              <form action="{{ route('bank-statements.reconcile') }}" method="POST" class="w-100 w-md-auto">
                @csrf
                <button class="btn btn-outline-ghost w-100" type="submit">{{ __('ui.payments.actions.reconcile') }}</button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header d-flex justify-content-between align-items-center">
          <div>
            <strong>{{ __('ui.payments.bank_table.title') }}</strong>
            <div class="mini text-muted">{{ __('ui.payments.helpers.bank_subtitle') }}</div>
          </div>
          <span class="badge bg-light text-dark">{{ $statements->count() }}</span>
        </div>
        <div class="panel-body">
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
  </main>
</div>
@endsection
