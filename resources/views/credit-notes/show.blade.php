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
      <a href="{{ route('credit-notes.index') }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> {{ __('ui.actions.back') }}</a>
    </div>

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
          <div class="title">{{ $note->type === 'debit' ? 'Debit Note' : 'Credit Note' }}</div>
          <div class="sub">{{ $note->customer_name ?? 'ลูกค้าไม่ระบุ' }}</div>
          <div class="number">{{ $note->number ?? 'Draft' }}</div>
        </div>
        <div class="cn-actions">
          <span class="cn-badge" style="color:{{ $statusColor[1] }};background:{{ $statusColor[0] }};border-color:{{ $statusColor[2] }}">
            <i class="bi bi-circle-fill" style="font-size:10px"></i>
            {{ ucfirst($note->status) }}
          </span>
          <a href="{{ route('credit-notes.edit', $note->number ?? $note->id) }}" class="cn-btn"><i class="bi bi-pencil"></i> Edit</a>
          <a href="{{ route('credit-notes.index') }}" class="cn-btn"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
      </div>

      <div class="cn-body">
        <div>
          <div class="cn-card">
            <div class="section-title"><i class="bi bi-receipt"></i> รายละเอียดเอกสาร</div>
            <div class="info-grid">
              <div class="info"><div class="label">Invoice</div><div class="value">{{ $note->invoice_number ?? '—' }}</div></div>
              <div class="info"><div class="label">วันที่</div><div class="value">{{ optional($note->issue_date)->format('Y-m-d') ?? '—' }}</div></div>
              <div class="info"><div class="label">เลข PO</div><div class="value">{{ $note->po_number ?? '—' }}</div></div>
              <div class="info"><div class="label">สถานะ</div><div class="value">{{ ucfirst($note->status) }}</div></div>
            </div>

            <div class="section-title mt-3"><i class="bi bi-card-checklist"></i> รายการสินค้า / บริการ</div>
            <table class="cn-table">
              <thead>
                <tr>
                  <th>Description</th>
                  <th style="width:100px">Qty</th>
                  <th style="width:140px">Unit Price</th>
                  <th style="width:140px">Line Total</th>
                </tr>
              </thead>
              <tbody>
                @forelse($note->items as $item)
                  <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->qty }}</td>
                    <td>{{ number_format($item->unit_price,2) }}</td>
                    <td>{{ number_format($item->line_total,2) }}</td>
                  </tr>
                @empty
                  <tr><td colspan="4" class="text-center text-muted">ไม่มีรายการสินค้า</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

        <div class="cn-sticky">
          <div class="cn-card">
            <div class="section-title"><i class="bi bi-person"></i> ลูกค้า</div>
            <div class="info"><div class="label">ชื่อ</div><div class="value">{{ $note->customer_name ?? '—' }}</div></div>
            <div class="info mt-2"><div class="label">เลขผู้เสียภาษี</div><div class="value">{{ $note->customer_tax_id ?? '—' }}</div></div>
            <div class="info mt-2"><div class="label">สาขา</div><div class="value">{{ $note->customer_branch_code ?? '—' }}</div></div>
            <div class="info mt-2"><div class="label">ที่อยู่</div><div class="value">{{ $note->customer_address ?? '—' }}</div></div>
          </div>

          <div class="cn-card cn-totals">
            <div class="section-title"><i class="bi bi-cash-coin"></i> Totals</div>
            <div class="row"><span>Subtotal</span><strong>{{ number_format($note->subtotal ?? 0,2) }}</strong></div>
            <div class="row"><span>Tax</span><strong>{{ number_format($note->tax ?? 0,2) }}</strong></div>
            <div class="row"><span>Total</span><strong>{{ number_format($note->total ?? 0,2) }}</strong></div>
          </div>

          <div class="cn-card">
            <div class="section-title"><i class="bi bi-check2-circle"></i> Approval</div>
            <div class="info" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
              <span class="cn-badge" style="background:#eef2ff;color:#0f172a;border-color:#d5ddff">{{ ucfirst($note->approval_status ?? 'draft') }} @if($note->approval_step) · Step {{ $note->approval_step }} @endif</span>
              <form action="{{ route('credit-notes.submit-approval', $note->number ?? $note->id) }}" method="POST">
                @csrf
                <button type="submit" class="cn-btn" style="padding:8px 12px">ส่งอนุมัติ</button>
              </form>
            </div>
            @if(optional(auth()->user())->role && in_array(strtolower(auth()->user()->role), ['approver','admin','manager']))
              <form action="{{ route('credit-notes.approve', $note->number ?? $note->id) }}" method="POST" class="mt-2" style="display:flex;gap:8px;flex-wrap:wrap">
                @csrf
                <input type="text" name="comment" class="form-control" placeholder="หมายเหตุ (ถ้ามี)" style="flex:1 1 150px">
                <button type="submit" class="cn-btn primary" style="padding:8px 14px">Approve</button>
              </form>
              <form action="{{ route('credit-notes.reject', $note->number ?? $note->id) }}" method="POST" class="mt-2" style="display:flex;gap:8px;flex-wrap:wrap">
                @csrf
                <input type="text" name="comment" class="form-control" placeholder="เหตุผล" style="flex:1 1 150px">
                <button type="submit" class="cn-btn" style="padding:8px 14px;color:#b42318;border-color:#f5c2c7">Reject</button>
              </form>
            @endif

            <div class="section-title mt-3" style="font-size:14px"><i class="bi bi-list-check"></i> Steps</div>
            <ul class="list-unstyled" style="margin:0;padding:0;display:flex;flex-direction:column;gap:8px">
              @forelse($note->approvals as $approval)
                <li style="border:1px solid var(--line);padding:10px;border-radius:12px">
                  <div class="d-flex justify-content-between">
                    <div><strong>Step {{ $approval->step }}</strong> · {{ ucfirst($approval->role) }}</div>
                    <div class="text-muted" style="font-size:12px">{{ $approval->acted_at?->format('Y-m-d H:i') }}</div>
                  </div>
                  <div class="text-muted" style="font-size:13px">สถานะ: {{ ucfirst($approval->status) }} @if($approval->comment) · {{ $approval->comment }} @endif</div>
                  @if($approval->user)
                    <div class="text-muted" style="font-size:12px">โดย {{ $approval->user->name }}</div>
                  @endif
                </li>
              @empty
                <li class="text-muted">ยังไม่มีขั้นตอนอนุมัติ</li>
              @endforelse
            </ul>

            <div class="section-title mt-3" style="font-size:14px"><i class="bi bi-clock-history"></i> Audit trail</div>
            <ul class="list-unstyled" style="margin:0;padding:0;display:flex;flex-direction:column;gap:8px">
              @forelse($note->auditLogs as $log)
                <li style="border:1px dashed var(--line);padding:10px;border-radius:12px">
                  <div class="d-flex justify-content-between">
                    <div><strong>{{ ucfirst(str_replace('_',' ', $log->action)) }}</strong></div>
                    <div class="text-muted" style="font-size:12px">{{ optional($log->created_at)->format('Y-m-d H:i') }}</div>
                  </div>
                  @if($log->user)
                    <div class="text-muted" style="font-size:12px">โดย {{ $log->user->name }}</div>
                  @endif
                  @if($log->changes)
                    <div class="text-muted" style="font-size:12px">
                      @foreach($log->changes as $field => $change)
                        <div>{{ ucfirst($field) }}: {{ data_get($change,'from') ?? '—' }} → {{ data_get($change,'to') ?? '—' }}</div>
                      @endforeach
                    </div>
                  @endif
                </li>
              @empty
                <li class="text-muted">ยังไม่มีบันทึก</li>
              @endforelse
            </ul>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
@endsection
