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
      <form class="search" method="GET" action="{{ route('dashboard') }}">
        <i class="bi bi-search"></i>
        <input
          class="form-control"
          name="q"
          value="{{ $q }}"
          placeholder="Search by account, date or amount"
          aria-label="Search transactions"
        >
      </form>
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
              @if($q)
                <p class="text-muted small mb-3">Search results for “{{ $q }}”.</p>
              @endif
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
                      <tr>
                        <td colspan="4" class="text-center text-muted">
                          @if($q)
                            No transactions match your search.
                          @else
                            No transactions yet.
                          @endif
                        </td>
                      </tr>
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
