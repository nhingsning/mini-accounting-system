@extends('layouts.app')

@section('content')
@php
  $typeLabel = ['credit' => 'Credit Note','debit' => 'Debit Note'];
@endphp
<div class="container" style="max-width:1100px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold mb-1">Credit / Debit Notes</h3>
      <div class="text-muted">รายการปรับยอดจากใบแจ้งหนี้</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('credit-notes.create') }}" class="btn btn-primary">+ สร้างเอกสาร</a>
    </div>
  </div>

  <form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-4">
      <label class="form-label">ค้นหา</label>
      <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="เลขเอกสาร / ลูกค้า / Invoice">
    </div>
    <div class="col-md-3">
      <label class="form-label">ประเภท</label>
      <select name="type" class="form-select">
        <option value="">ทั้งหมด</option>
        <option value="credit" {{ $type==='credit'?'selected':'' }}>Credit Note</option>
        <option value="debit" {{ $type==='debit'?'selected':'' }}>Debit Note</option>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-outline-secondary w-100" type="submit">ค้นหา</button>
    </div>
  </form>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>เลขเอกสาร</th>
            <th>ประเภท</th>
            <th>Invoice</th>
            <th>ลูกค้า</th>
            <th>สถานะ</th>
            <th class="text-end">ยอดรวม</th>
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
              <td class="text-end">
                <a href="{{ route('credit-notes.edit', $note->number ?? $note->id) }}" class="btn btn-sm btn-outline-primary">แก้ไข</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">ยังไม่มีข้อมูล</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="p-3">{{ $notes->withQueryString()->links() }}</div>
  </div>
</div>
@endsection
