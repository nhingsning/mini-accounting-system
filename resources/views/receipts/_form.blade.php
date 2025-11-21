@php
  $isEdit = isset($receipt);
  $action = $isEdit ? route('receipts.update', $receipt->number ?? $receipt->id) : route('receipts.store');
@endphp

<form class="panel" method="POST" action="{{ $action }}">
  @csrf
  @if($isEdit)
    @method('PUT')
  @endif

  <div class="panel-header d-flex align-items-center justify-content-between">
    <strong>{{ $isEdit ? 'Edit Receipt' : 'Create Receipt' }}</strong>
    <a href="{{ route('receipts.index') }}" class="btn btn-light btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
  </div>
  <div class="panel-body">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Receipt No. (optional)</label>
        <input name="number" class="form-control" value="{{ old('number', $isEdit ? $receipt->number : '') }}" placeholder="ปล่อยว่างให้ออกเลขเอง">
      </div>
      <div class="col-md-4">
        <label class="form-label">Invoice No.</label>
        <input name="invoice_number" class="form-control" value="{{ old('invoice_number', $isEdit ? $receipt->invoice_number : ($invoice->number ?? '')) }}" placeholder="INV...">
        <input type="hidden" name="invoice_id" value="{{ old('invoice_id', $isEdit ? $receipt->invoice_id : ($invoice->id ?? '')) }}">
      </div>
      <div class="col-md-4">
        <label class="form-label">Status</label>
        @php $status = old('status', $isEdit ? $receipt->status : 'draft'); @endphp
        <select name="status" class="form-select">
          <option value="draft" {{ $status==='draft'?'selected':'' }}>Draft</option>
          <option value="issued" {{ $status==='issued'?'selected':'' }}>Issued</option>
          <option value="paid" {{ $status==='paid'?'selected':'' }}>Paid</option>
          <option value="cancelled" {{ $status==='cancelled'?'selected':'' }}>Cancelled</option>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Customer Name</label>
        <input name="customer_name" class="form-control" value="{{ old('customer_name', $isEdit ? $receipt->customer_name : ($invoice->customer_name ?? '')) }}" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Issue Date</label>
        <input type="date" name="issue_date" class="form-control" value="{{ old('issue_date', ($isEdit ? optional($receipt->issue_date)->toDateString() : now()->toDateString())) }}">
      </div>
      <div class="col-md-3">
        <label class="form-label">Total</label>
        <input type="number" step="0.01" name="total" class="form-control" value="{{ old('total', $isEdit ? $receipt->total : ($invoice->total ?? 0)) }}" required>
      </div>

      <div class="col-md-8">
        <label class="form-label">Customer Address</label>
        <textarea name="customer_address" rows="2" class="form-control">{{ old('customer_address', $isEdit ? $receipt->customer_address : ($invoice->customer_address ?? '')) }}</textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label">Tax ID</label>
        <input name="customer_tax_id" class="form-control" value="{{ old('customer_tax_id', $isEdit ? $receipt->customer_tax_id : ($invoice->customer_tax_id ?? '')) }}">
      </div>

      <div class="col-md-4">
        <label class="form-label">Branch Type</label>
        @php $bt = old('customer_branch_type', $isEdit ? $receipt->customer_branch_type : ($invoice->customer_branch_type ?? '')); @endphp
        <select name="customer_branch_type" class="form-select">
          <option value="" {{ $bt===''?'selected':'' }}>—</option>
          <option value="head" {{ $bt==='head'?'selected':'' }}>Head Office</option>
          <option value="branch" {{ $bt==='branch'?'selected':'' }}>Branch</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Branch Code</label>
        <input name="customer_branch_code" class="form-control" value="{{ old('customer_branch_code', $isEdit ? $receipt->customer_branch_code : ($invoice->customer_branch_code ?? '')) }}">
      </div>
      <div class="col-md-4">
        <label class="form-label">Currency</label>
        <input name="currency" class="form-control" value="{{ old('currency', $isEdit ? $receipt->currency : ($invoice->currency ?? 'THB')) }}">
      </div>
    </div>
  </div>
  <div class="panel-footer text-end">
    <button class="btn btn-brand" type="submit">{{ $isEdit ? 'Update Receipt' : 'Save Receipt' }}</button>
  </div>
</form>
