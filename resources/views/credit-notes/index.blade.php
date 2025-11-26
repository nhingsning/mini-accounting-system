@extends('layouts.app')

@section('title', __('ui.credit_notes.title'))

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')

  <main>
    <div class="topbar">
      <button id="menuToggle" class="btn btn-soft rounded-circle p-2 d-lg-none" aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>
      <h2 class="m-0">{{ __('ui.credit_notes.title') }}</h2>
      <div class="ms-auto"></div>
      <a href="{{ route('credit-notes.create') }}" class="btn btn-brand d-none d-sm-inline-flex"><i class="bi bi-plus-lg me-1"></i> {{ __('ui.credit_notes.new') }}</a>
      <a href="{{ route('credit-notes.create') }}" class="btn btn-brand d-sm-none"><i class="bi bi-plus-lg"></i></a>
    </div>

    @php
      $typeLabel = [
        'credit' => __('ui.credit_notes.filters.credit'),
        'debit' => __('ui.credit_notes.filters.debit'),
      ];
    @endphp
    <div class="container-fluid py-3">
      <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-md-4">
          <label class="form-label">{{ __('ui.actions.search') }}</label>
          <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="{{ __('ui.credit_notes.search_placeholder') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label">{{ __('ui.credit_notes.filters.type') }}</label>
          <select name="type" class="form-select">
            <option value="">{{ __('ui.credit_notes.filters.all') }}</option>
            <option value="credit" {{ $type==='credit'?'selected':'' }}>{{ __('ui.credit_notes.filters.credit') }}</option>
            <option value="debit" {{ $type==='debit'?'selected':'' }}>{{ __('ui.credit_notes.filters.debit') }}</option>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-outline-secondary w-100" type="submit">{{ __('ui.actions.search') }}</button>
        </div>
      </form>

      <div class="card shadow-sm">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>{{ __('ui.credit_notes.table.number') }}</th>
                <th>{{ __('ui.credit_notes.table.type') }}</th>
                <th>{{ __('ui.credit_notes.table.invoice') }}</th>
                <th>{{ __('ui.credit_notes.table.customer') }}</th>
                <th>{{ __('ui.credit_notes.table.status') }}</th>
                <th class="text-end">{{ __('ui.credit_notes.table.total') }}</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @forelse($notes as $note)
                <tr>
                  <td><a href="{{ route('credit-notes.show', $note->number ?? $note->id) }}" class="fw-bold text-decoration-none">{{ $note->number ?? 'Draft' }}</a></td>
                  <td>{{ $typeLabel[$note->type] ?? ucfirst($note->type) }}</td>
                  <td>{{ $note->invoice_number ?? '—' }}</td>
                  <td>{{ $note->customer_name ?? '—' }}</td>
                  <td>{{ ucfirst($note->status) }}</td>
                  <td class="text-end">{{ number_format($note->total ?? 0,2) }}</td>
                  <td class="text-end d-flex justify-content-end gap-2">
                    <a href="{{ route('credit-notes.show', $note->number ?? $note->id) }}" class="btn btn-sm btn-outline-secondary">{{ __('ui.actions.view') }}</a>
                    <a href="{{ route('credit-notes.edit', $note->number ?? $note->id) }}" class="btn btn-sm btn-outline-primary">{{ __('ui.actions.edit') }}</a>
                    <form action="{{ route('credit-notes.destroy', $note->number ?? $note->id) }}" method="POST" class="d-inline" onsubmit="return confirm('{{ __('ui.actions.confirm_delete') }}');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('ui.actions.delete') }}</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="7" class="text-center text-muted py-4">{{ __('ui.credit_notes.table.empty') }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="p-3">{{ $notes->withQueryString()->links() }}</div>
      </div>
    </div>
  </main>
</div>
@endsection
