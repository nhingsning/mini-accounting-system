@extends('layouts.app')
@section('title', $customer->exists ? 'Edit Customer' : 'New Customer')

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')
  <main>
    <div class="topbar">
      <h2 class="m-0">{{ $customer->exists ? 'Edit: '.$customer->name : 'Create Customer' }}</h2>
    </div>

    <div class="container-fluid py-3">
      {{-- แสดง error ทั้งหมดด้านบน --}}
      @if ($errors->any())
        <div class="alert alert-danger">
          <div class="fw-600 mb-1">Please fix the following:</div>
          <ul class="m-0 ps-3">
            @foreach ($errors->all() as $e)
              <li>{{ $e }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form class="panel" method="POST"
            action="{{ $customer->exists ? route('customers.update',$customer) : route('customers.store') }}">
        @csrf
        @if($customer->exists) @method('PUT') @endif

        <div class="panel-header"><strong>Customer Data</strong></div>
        <div class="panel-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label required">Customer Name</label>
              <input name="name" value="{{ old('name',$customer->name) }}" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label required">Address</label>
              <textarea name="address_show" class="form-control" rows="3" required>{{ old('address_show',$customer->address_show) }}</textarea>
            </div>

            <div class="col-md-6">
              <label class="form-label">Customer Name (no show)</label>
              <input name="name_private" value="{{ old('name_private',$customer->name_private) }}" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Address (no show)</label>
              <textarea name="address_private" class="form-control" rows="3">{{ old('address_private',$customer->address_private) }}</textarea>
            </div>

            <div class="col-md-3">
              <label class="form-label">Tax ID</label>
              <input name="tax_id" value="{{ old('tax_id',$customer->tax_id) }}" class="form-control">
            </div>


            <div class="col-md-3">
              <label class="form-label required">Tel.</label>
              <input name="tel" value="{{ old('tel',$customer->tel) }}" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Fax</label>
              <input name="fax" value="{{ old('fax',$customer->fax) }}" class="form-control">
            </div>

            <div class="col-md-4">
              <label class="form-label required">PaymentTerms</label>
              <input name="payment_terms" value="{{ old('payment_terms',$customer->payment_terms) }}" class="form-control" required>
            </div>

            {{-- ขึ้นบรรทัดใหม่ --}}
            <div class="col-12"></div>
{{-- ===== Head / Branch (inline branch code) ===== --}}
<div class="col-md-3">
  <label class="form-label required d-block">Head/Branch</label>

  <div class="form-check mb-1">
    <input class="form-check-input" type="radio" name="office_type" id="of_head" value="head"
      {{ old('office_type', $customer->office_type ?? 'head') === 'head' ? 'checked' : '' }}>
    <label class="form-check-label" for="of_head">Head Office</label>
  </div>

  <div class="form-check d-flex align-items-center gap-2">
    <input class="form-check-input" type="radio" name="office_type" id="of_branch" value="branch"
      {{ old('office_type', $customer->office_type) === 'branch' ? 'checked' : '' }}>
    <label class="form-check-label me-1" for="of_branch">Branch</label>

    <input
      type="text"
      name="branch_code"
      id="branch_code"
      class="form-control form-control-sm d-inline-block"
      style="max-width: 160px;"
      placeholder="Branch Code"
      value="{{ old('branch_code', $customer->branch_code) }}"
    >
  </div>
  <div class="form-text">กรอกเฉพาะตอนเป็นสาขา</div>
</div>

          </div>

          <hr class="my-4">

          <h6 class="mb-2">Customer Contact Person</h6>
          <div id="contactRows">
            @php $rows = old('contacts', $customer->contacts?->toArray() ?? []); @endphp
            @forelse(($rows ?: [[]]) as $i => $row)
              <div class="row g-2 align-items-end mb-2 contact-row">
                <div class="col-md-3">
                  <label class="form-label required">Contact Name</label>
                  <input name="contacts[{{ $i }}][contact_name]" value="{{ $row['contact_name'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Department</label>
                  <input name="contacts[{{ $i }}][department]" value="{{ $row['department'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Position</label>
                  <input name="contacts[{{ $i }}][position]" value="{{ $row['position'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Mobile Phone</label>
                  <input name="contacts[{{ $i }}][mobile]" value="{{ $row['mobile'] ?? '' }}" class="form-control">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Email</label>
                  <input name="contacts[{{ $i }}][email]" value="{{ $row['email'] ?? '' }}" type="email" class="form-control">
                </div>
              </div>
            @empty @endforelse
          </div>
          <button type="button" class="btn btn-soft" onclick="addContactRow()">+ Add Contact</button>
        </div>

        <div class="panel-footer d-flex gap-2 justify-content-end">
          <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
          <button class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </main>
</div>

@push('scripts')
<script>
let contactIndex = document.querySelectorAll('#contactRows .contact-row').length || 0;
function addContactRow() {
  const wrap = document.getElementById('contactRows');
  wrap.insertAdjacentHTML('beforeend', `
    <div class="row g-2 align-items-end mb-2 contact-row">
      <div class="col-md-3"><label class="form-label required">Contact Name</label>
        <input name="contacts[${contactIndex}][contact_name]" class="form-control">
      </div>
      <div class="col-md-2"><label class="form-label">Department</label>
        <input name="contacts[${contactIndex}][department]" class="form-control">
      </div>
      <div class="col-md-2"><label class="form-label">Position</label>
        <input name="contacts[${contactIndex}][position]" class="form-control">
      </div>
      <div class="col-md-2"><label class="form-label">Mobile Phone</label>
        <input name="contacts[${contactIndex}][mobile]" class="form-control">
      </div>
      <div class="col-md-3"><label class="form-label">Email</label>
        <input name="contacts[${contactIndex}][email]" type="email" class="form-control">
      </div>
    </div>
  `);
  contactIndex++;
}

function toggleBranchCode(){
  const isBranch = document.getElementById('of_branch')?.checked;
  const code = document.getElementById('branch_code');
  if (!code) return;
  code.toggleAttribute('required', !!isBranch);
  code.disabled = !isBranch;
}
document.getElementById('of_head')?.addEventListener('change', toggleBranchCode);
document.getElementById('of_branch')?.addEventListener('change', toggleBranchCode);
document.addEventListener('DOMContentLoaded', toggleBranchCode);


</script>

@endpush
@endsection
