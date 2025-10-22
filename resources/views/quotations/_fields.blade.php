@php
  // ไม่มี $quotation ก็ไม่เป็นไร ใช้ null
  $q = $quotation ?? null;
  $status = old('status', $q->status ?? 'draft');
@endphp

<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">Customer Name</label>
    <input name="customer_name"
           class="form-control @error('customer_name') is-invalid @enderror"
           value="{{ old('customer_name', $q->customer_name ?? '') }}"
           required>
    @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-3">
    <label class="form-label">Issue Date</label>
    <input type="date" name="issue_date"
           class="form-control @error('issue_date') is-invalid @enderror"
           value="{{ old('issue_date', optional($q->issue_date ?? $q->created_at ?? now())->format('Y-m-d')) }}">
    @error('issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-3">
    <label class="form-label">Total</label>
    <input type="number" step="0.01" name="total"
           class="form-control @error('total') is-invalid @enderror"
           value="{{ old('total', $q->total ?? 0) }}" required>
    @error('total')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="col-md-3">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
      <option value="draft"    {{ $status==='draft'?'selected':'' }}>Draft</option>
      <option value="approved" {{ $status==='approved'?'selected':'' }}>Approved</option>
      <option value="rejected" {{ $status==='rejected'?'selected':'' }}>Rejected</option>
    </select>
  </div>
</div>
