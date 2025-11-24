@extends('layouts.app')
@section('title','Dashboard')

@section('body')
<div class="layout">
  @includeIf('partials.sidebar')

  <main>
    {{-- Topbar --}}
    <div class="topbar">
      <button id="menuToggle" class="btn btn-soft rounded-circle p-2 d-lg-none" aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>
      <div class="search">
        <i class="bi bi-search"></i>
        <input class="form-control" placeholder="Search by account, date or amount">
      </div>
      <button class="btn btn-light rounded-circle p-2"><i class="bi bi-bell"></i></button>
      <div class="avatar">AB</div>
    </div>

    {{-- ===== Content ===== --}}
    <div class="container-fluid py-3">
      <div class="row g-3">
        {{-- Left --}}
        <div class="col-xl-8">
          {{-- Expenses Chart --}}
          <div class="panel mb-3">
            <div class="panel-header">
              <strong>Expenses Overview</strong>
              <span class="mini">{{ now()->year }}</span>
            </div>
            <div class="panel-body">
              <div class="chart-container">
                <canvas id="barChart" height="100"></canvas>
              </div>
            </div>
          </div>

          {{-- KPIs --}}
          <div class="row g-3">
            <div class="col-md-6">
              <div class="panel p-3 kpi">
                <div class="mini">Accounts Receivable</div>
                <div class="value">
                  ${{ number_format(($kpis['ar'] ?? 0),2) }} <span class="mini">(THB)</span>
                </div>
                <div class="mini text-success">▲ compared to last month</div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="panel p-3 kpi">
                <div class="mini">Accounts Payable</div>
                <div class="value">
                  ${{ number_format(($kpis['ap'] ?? 0),2) }} <span class="mini">(THB)</span>
                </div>
                <div class="mini text-danger">▼ compared to last month</div>
              </div>
            </div>
          </div>

          {{-- Transaction History --}}
          <div class="panel mt-3">
            <div class="panel-header">
              <strong>Transaction History</strong>
              <span class="mini">{{ $periodText ?? '' }}</span>
            </div>
            <div class="panel-body">
              <div class="table-responsive">
                <table class="table align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>Transactions</th>
                      <th>Date</th>
                      <th class="text-end">Amount</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse(($recent ?? []) as $row)
                      <tr>
                        <td>
                          <span class="badge-dot" style="background:#2B4A72"></span>
                          {{ $row->customer_name ?? ('Invoice #'.$row->id) }}
                        </td>
                        <td>{{ \Carbon\Carbon::parse($row->d)->format('M d, Y') }}</td>
                        <td class="text-end">{{ number_format($row->total,2) }}THB</td>
                        <td>
                          @php
                            $status = strtolower($row->status ?? '');
                            $badge  = $status==='paid' ? 'success' : ($status==='cancelled' ? 'danger' : 'warning');
                            $label  = $status ?: ($row->total<0 ? 'Expense' : 'Pending');
                          @endphp
                          <span class="badge text-bg-{{ $badge }}">{{ ucfirst($label) }}</span>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="4" class="text-center text-muted">No transactions yet.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          {{-- Quotations overview --}}
          <div class="panel mt-3">
            <div class="panel-header d-flex justify-content-between align-items-center">
              <div>
                <strong>Quotations</strong>
                <span class="mini">Latest activity</span>
              </div>
              <a href="{{ route('quotations.index') }}" class="btn btn-sm btn-light">View all</a>
            </div>
            <div class="panel-body">
              <div class="table-responsive">
                <table class="table align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>QT No.</th>
                      <th>Customer</th>
                      <th>Date</th>
                      <th class="text-end">Total</th>
                      <th>Status</th>
                      <th class="text-end">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse(($quotations ?? []) as $q)
                      @php
                        $qtKey = $q->number ?? $q->id;
                        $status = strtolower($q->status ?? 'draft');
                        $badge  = $status === 'approved' ? 'success' : ($status === 'rejected' ? 'danger' : ($status === 'sent' ? 'info' : 'secondary'));
                        $date   = $q->d ?? $q->created_at ?? now();
                      @endphp
                      <tr>
                        <td>
                          <a href="{{ route('quotations.show', $qtKey) }}" class="text-decoration-none">{{ $q->number ?? ('QT'.\Carbon\Carbon::parse($date)->format('Ym').'-'.str_pad($q->id,4,'0',STR_PAD_LEFT)) }}</a>
                        </td>
                        <td>{{ $q->customer_name ?? '—' }}</td>
                        <td>{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</td>
                        <td class="text-end">{{ number_format((float)($q->total ?? 0), 2) }}</td>
                        <td><span class="badge text-bg-{{ $badge }}">{{ ucfirst($status) }}</span></td>
                        <td class="text-end" style="white-space:nowrap">
                          <a href="{{ route('quotations.show', $qtKey) }}" class="btn btn-sm btn-light" title="View"><i class="bi bi-eye"></i></a>
                          <form action="{{ route('quotations.copy', $qtKey) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light" title="Copy"><i class="bi bi-files"></i></button>
                          </form>
                          <form action="{{ route('quotations.convert.invoice', $qtKey) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light" title="Convert to Invoice"><i class="bi bi-receipt"></i></button>
                          </form>
                          <form action="{{ route('quotations.convert.po', $qtKey) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-light" title="Convert to PO"><i class="bi bi-file-earmark-arrow-down"></i></button>
                          </form>
                          <form action="{{ route('quotations.destroy', $qtKey) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete quotation {{ $q->number ?? $qtKey }} ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></button>
                          </form>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="6" class="text-center text-muted">No quotations yet.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        {{-- Right --}}
        <div class="col-xl-4">
          <div class="panel mb-3 p-3">
            <div class="mini">Net Profit</div>
            <div class="value">
              ${{ number_format(($kpis['netProfit'] ?? 0),2) }} <span class="mini">(THB)</span>
            </div>
            <div class="mini">From {{ $periodText ?? '' }}</div>
          </div>

          <div class="panel mb-3 p-3">
            <div class="mini">Closing Balance</div>
            <div class="value">
              ${{ number_format(($kpis['closing'] ?? 0),2) }} <span class="mini">(THB)</span>
            </div>
            <div class="mini">As of {{ now()->format('M d, Y') }}</div>
          </div>

          <div class="panel p-3">
            <div class="mini mb-2">Expenses Breakdown</div>
            <div class="chart-container">
              <canvas id="donutChart" height="120"></canvas>
            </div>
          </div>

          <div class="panel mt-3 p-3">
            <div class="panel-header">
              <strong>Quotation History</strong>
              <span class="mini">Recent changes</span>
            </div>
            <div class="panel-body">
              <ul class="list-unstyled mb-0">
                @forelse(($history ?? []) as $log)
                  <li class="mb-3">
                    <div class="d-flex justify-content-between">
                      <div>
                        <div class="fw-semibold">{{ ucfirst($log->action ?? 'update') }} – {{ $log->number ?? 'QT#'.$log->id }}</div>
                        <div class="mini text-muted">{{ $log->customer_name ?? '—' }}</div>
                        <div class="mini">{{ $log->description ?? 'Updated details' }}</div>
                      </div>
                      <div class="text-end mini">
                        <div>{{ \Carbon\Carbon::parse($log->created_at)->format('M d, H:i') }}</div>
                        @if(!empty($log->user_name))
                          <div class="text-muted">{{ $log->user_name }}</div>
                        @endif
                      </div>
                    </div>
                  </li>
                @empty
                  <li class="text-muted">No history yet.</li>
                @endforelse
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- ===== Safe PHP → JS bridge (หลีกเลี่ยง @json + ?? parser bug) ===== --}}
    @php
      $monthsSafe    = $chartMonths ?? ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      $barDataSafe   = $barData ?? array_fill(0, 12, 0);
      $breakdownSafe = $breakdown ?? [];
    @endphp

    @push('scripts')
      <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
      <script>
        const c1 = '#2B4A72', c2 = '#6f9ad0';
        const months    = @json($monthsSafe);
        const barData   = @json($barDataSafe);
        const breakdown = @json($breakdownSafe);

        // Bar chart
        new Chart(document.getElementById('barChart'),{
          type:'bar',
          data:{ labels: months, datasets:[{
            data: barData,
            backgroundColor:(ctx)=>ctx.dataIndex===3?c1:'#e8edfb',
            borderRadius:8, maxBarThickness:24
          }]},
          options:{ plugins:{legend:{display:false}},
            scales:{ x:{grid:{display:false}},
                     y:{grid:{color:'#eef1f7'}, ticks:{callback:v=>'$'+(v/1000)+'k'}} } }
        });

        // Donut chart
        const donutLabels = breakdown.map(b=>b.label);
        const donutValues = breakdown.map(b=>b.amount);
        new Chart(document.getElementById('donutChart'),{
          type:'doughnut',
          data:{ labels:donutLabels, datasets:[{
            data:donutValues,
            backgroundColor:[c1,c2,'#9fb8e3','#bcd0ee','#d8e2f6','#e8edfb'],
            borderWidth:0
          }]},
          options:{ cutout:'65%', plugins:{ legend:{ display:false } } }
        });
      </script>
    @endpush
  </main>
</div>
@endsection
