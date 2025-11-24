@extends('layouts.app')

@section('content')
@php
  $note->loadMissing(['invoice','items' => fn($q)=>$q->orderBy('id')]);
  $statusColor = [
    'draft' => ['#fef3c7','#f59e0b','#fcd34d'],
    'issued' => ['#e9f2fb','#31689E','#c5dbf1'],
    'cancelled' => ['#fee2e2','#b91c1c','#fecaca'],
  ][$note->status] ?? ['#e5e7eb','#374151','#d1d5db'];
@endphp
<style>
:root{--brand:#31689E;--ink:#0f172a;--muted:#6b7280;--line:#e5e7eb;--card:#fff;--bg:#f8fafc}
.cn-shell{max-width:1100px;margin:0 auto;padding:18px 18px 28px}
.cn-header{background:linear-gradient(135deg,#31689E,#1f4d7b);border-radius:18px;padding:16px 18px;color:#fff;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
.cn-h-left .title{font-size:22px;font-weight:800;letter-spacing:-0.01em;margin-bottom:4px}
.cn-h-left .sub{opacity:0.9;font-size:13px}
.cn-h-left .number{font-size:18px;font-weight:800;letter-spacing:0.06em}
.cn-badge{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:6px 12px;font-weight:700;font-size:12px;border:1px solid rgba(255,255,255,0.4);background:rgba(255,255,255,0.12)}
.cn-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.cn-btn{display:inline-flex;align-items:center;gap:6px;border-radius:10px;padding:10px 14px;font-weight:700;text-decoration:none;border:1px solid #dbe3ef;background:#fff;color:#0f172a;box-shadow:0 10px 25px -20px rgba(15,23,42,0.45)}
.cn-btn.primary{background:#31689E;color:#fff;border-color:#31689E;box-shadow:0 10px 25px -20px rgba(49,104,158,0.8)}
.cn-body{display:grid;grid-template-columns:1fr 320px;gap:18px;margin-top:16px}
@media(max-width:1024px){.cn-body{grid-template-columns:1fr}}
.cn-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:16px 18px;box-shadow:0 16px 50px -35px rgba(15,23,42,0.35)}
.section-title{font-weight:800;color:var(--ink);margin-bottom:10px;font-size:15px;display:flex;align-items:center;gap:8px}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.info{padding:10px 12px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px}
.info .label{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em;font-weight:800;margin-bottom:4px}
.info .value{font-weight:800;color:var(--ink)}
.cn-table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid var(--line);border-radius:12px;overflow:hidden;margin-top:6px}
.cn-table thead th{background:#edf4fb;border:0;padding:10px 12px;font-weight:800;color:#1f2937}
.cn-table tbody td{background:#fff;border-bottom:1px solid var(--line);padding:10px 12px;vertical-align:middle}
.cn-sticky{position:sticky;top:12px;display:flex;flex-direction:column;gap:12px}
.cn-totals .row{display:flex;justify-content:space-between;margin:6px 0;font-size:14px}
.cn-totals .row strong{font-weight:800;color:var(--ink)}
</style>
<div class="cn-shell">
  <div class="cn-header">
    <div class="cn-h-left">
      <div class="sub">{{ $note->type === 'debit' ? 'Debit Note' : 'Credit Note' }}</div>
      <div class="title">{{ $note->customer_name ?: 'ลูกค้าไม่ระบุ' }}</div>
      <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <span class="number">{{ $note->number ?? 'Draft' }}</span>
        <span class="cn-badge" style="color:{{ $statusColor[1] }};border-color:{{ $statusColor[2] }};background:{{ $statusColor[0] }};">{{ ucfirst($note->status) }}</span>
        @if($note->invoice_number)
          <span class="cn-badge" style="background:#fef3c7;border-color:#fcd34d;color:#92400e;">INV {{ $note->invoice_number }}</span>
        @endif
      </div>
    </div>
    <div class="cn-actions">
      <a href="{{ route('credit-notes.edit', $note->number ?? $note->id) }}" class="cn-btn">แก้ไข</a>
      <a href="{{ route('credit-notes.index') }}" class="cn-btn">กลับหน้ารายการ</a>
    </div>
  </div>

  <div class="cn-body">
    <div class="cn-card">
      <div class="section-title">ข้อมูลลูกค้า / เอกสาร</div>
      <div class="info-grid">
        <div class="info"><div class="label">ชื่อลูกค้า</div><div class="value">{{ $note->customer_name ?? '—' }}</div></div>
        <div class="info"><div class="label">Tax ID</div><div class="value">{{ $note->customer_tax_id ?? '—' }}</div></div>
        <div class="info"><div class="label">Branch</div><div class="value">{{ $note->customer_branch_type ? ucfirst($note->customer_branch_type) : '—' }} {{ $note->customer_branch_code }}</div></div>
        <div class="info"><div class="label">วันที่</div><div class="value">{{ optional($note->issue_date)->format('Y-m-d') ?? '—' }}</div></div>
        <div class="info" style="grid-column:1/-1"><div class="label">ที่อยู่</div><div class="value">{{ $note->customer_address ?? '—' }}</div></div>
        <div class="info" style="grid-column:1/-1"><div class="label">เหตุผล</div><div class="value">{{ $note->reason ?? '—' }}</div></div>
      </div>

      <div class="section-title" style="margin-top:16px">รายการสินค้า/บริการ</div>
      <table class="cn-table">
        <thead><tr><th>รายการ</th><th style="width:120px" class="text-end">Qty</th><th style="width:140px" class="text-end">Unit Price</th><th style="width:140px" class="text-end">Line Total</th></tr></thead>
        <tbody>
          @forelse($note->items as $it)
            <tr>
              <td>{{ $it->description ?? '—' }} @if($it->unit)<div style="color:#6b7280;font-size:12px;margin-top:2px">หน่วย: {{ $it->unit }}</div>@endif</td>
              <td style="text-align:right">{{ number_format($it->qty ?? 1, 2) }}</td>
              <td style="text-align:right">{{ number_format($it->unit_price ?? 0, 2) }}</td>
              <td style="text-align:right">{{ number_format($it->line_total ?? 0, 2) }}</td>
            </tr>
          @empty
            <tr><td colspan="4" style="text-align:center;color:#6b7280;padding:14px">ไม่มีรายการ</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="cn-card cn-sticky">
      <div class="section-title">สรุปยอด</div>
      <div class="cn-totals">
        <div class="row"><span>Subtotal</span><strong>{{ number_format($note->subtotal ?? 0,2) }}</strong></div>
        <div class="row"><span>Tax</span><strong>{{ number_format($note->tax ?? 0,2) }}</strong></div>
        <div class="row"><span>Total</span><strong>{{ number_format($note->total ?? 0,2) }}</strong></div>
        <div class="row"><span>Currency</span><strong>{{ $note->currency ?? 'THB' }}</strong></div>
      </div>
    </div>
  </div>
</div>
@endsection
