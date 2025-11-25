<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // ===== Config / helpers =====
        $driver  = DB::connection()->getDriverName();           // sqlite | mysql | pgsql ...
        $today   = Carbon::today();
        $fromY   = $today->copy()->startOfYear();
        $dateCol = $this->pickDateColumn('invoices', ['issue_date','issued_at','created_at']);
        $dueCol  = Schema::hasColumn('invoices', 'due_date') ? 'due_date' : $dateCol;

        // ===== Monthly Expenses (สรุปเป็น 12 เดือน) =====
        // NOTE: ปรับ where() ให้ตรงกับนิยาม "ค่าใช้จ่าย" ของหนิง
        //  - ถ้าเก็บค่าใช้จ่ายเป็นเลขติดลบ ให้ where('total','<',0)
        //  - ถ้ามีคอลัมน์ type = 'expense' ให้เปลี่ยนเป็น where('type','expense')
        $monthExpr = $this->monthExtract($driver, $dateCol);    // คืน string expression ของเดือน 01..12
        $base = DB::table('invoices')->selectRaw("$monthExpr as m, SUM(total) as s")
                 ->whereBetween($dateCol, [$fromY, $today]);

        // ทางเลือก: ใช้ total<0 เป็น "ค่าใช้จ่าย"
        $monthlyExpenses = (clone $base)
            ->where('total', '<', 0)
            ->groupBy('m')
            ->pluck('s','m')
            ->all();

        // เตรียม array 12 เดือนให้ครบ
        $barData = [];
        for ($i=1;$i<=12;$i++){
            $key = str_pad((string)$i,2,'0',STR_PAD_LEFT);
            $barData[] = round(($monthlyExpenses[$key] ?? 0) * -1, 2); // กลับเป็นบวกเพื่อพล็อต
        }

        // ===== KPIs: AR/AP / Net Profit / Closing Balance =====
        // Receivable: ใบแจ้งหนี้ที่ยังไม่จ่าย (status != 'paid')
        $hasStatus = Schema::hasColumn('invoices','status');
        $arQuery = DB::table('invoices');
        if ($hasStatus) $arQuery->whereNotIn('status', ['paid','cancelled']);
        $accountsReceivable = (float) $arQuery->where('total','>',0)->sum('total');

        // Payable: ค่าใช้จ่ายที่ยังไม่จ่าย (ถ้าไม่มีสถานะ จะรวมทั้งก้อนค่าใช้จ่าย)
        $apQuery = DB::table('invoices')->where('total','<',0);
        if ($hasStatus) $apQuery->whereNotIn('status', ['paid','cancelled']);
        $accountsPayable = (float) $apQuery->sum('total'); // เป็นลบ
        $accountsPayableAbs = abs($accountsPayable);

        // Net Profit (ง่ายๆ): รายรับบวกทั้งหมด + ค่าใช้จ่าย (ค่าลบ)
        $revenue = (float) DB::table('invoices')->where('total','>',0)->sum('total');
        $expenses= (float) DB::table('invoices')->where('total','<',0)->sum('total'); // ค่าลบ
        $netProfit = $revenue + $expenses;

        // Closing Balance: ยอดสุทธิสะสมทั้งหมด
        $closingBalance = (float) DB::table('invoices')->sum('total');

        // ===== Receivables aging =====
        $agingBuckets = [
            'current' => 0,
            '1-30'    => 0,
            '31-60'   => 0,
            '61-90'   => 0,
            '90+'     => 0,
        ];

        $receivables = DB::table('invoices')
            ->select('total', $dueCol . ' as due', 'outstanding_total')
            ->where('total', '>', 0)
            ->get();

        foreach ($receivables as $inv) {
            $outstanding = (float) ($inv->outstanding_total ?? $inv->total ?? 0);
            if ($outstanding <= 0) continue;

            $dueDate = $inv->due ? Carbon::parse($inv->due) : null;
            $days    = $dueDate ? $dueDate->diffInDays($today, false) : -1;

            if ($days <= 0) {
                $agingBuckets['current'] += $outstanding;
            } elseif ($days <= 30) {
                $agingBuckets['1-30'] += $outstanding;
            } elseif ($days <= 60) {
                $agingBuckets['31-60'] += $outstanding;
            } elseif ($days <= 90) {
                $agingBuckets['61-90'] += $outstanding;
            } else {
                $agingBuckets['90+'] += $outstanding;
            }
        }

        // ===== Top customers / products =====
        $topCustomers = DB::table('invoices')
            ->select('customer_name', DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as invoices'))
            ->whereNotNull('customer_name')
            ->where('total', '>', 0)
            ->groupBy('customer_name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $topProducts = DB::table('invoice_items as ii')
            ->join('invoices as i', 'ii.invoice_id', '=', 'i.id')
            ->select('ii.description', DB::raw('SUM(ii.line_total) as revenue'), DB::raw('SUM(ii.qty) as units'))
            ->where('i.total', '>', 0)
            ->groupBy('ii.description')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // ===== Margin by invoice (uses subtotal when available) =====
        $marginRows = DB::table('invoices')
            ->select('id','number','subtotal','tax','total', $dateCol . ' as d')
            ->orderByDesc($dateCol)
            ->limit(12)
            ->get()
            ->map(function ($row) {
                $margin = (float) ($row->subtotal ?? ($row->total - ($row->tax ?? 0)));
                $total  = (float) ($row->total ?? 0);
                $rate   = $total > 0 ? round(($margin / $total) * 100, 1) : 0;
                $row->margin = $margin;
                $row->margin_rate = $rate;
                return $row;
            });

        // ===== Status heatmap (month x status) =====
        $yearExpr  = $this->yearExtract($driver, $dateCol);
        $heatRows  = DB::table('invoices')
            ->selectRaw("$yearExpr as y, $monthExpr as m, lower(coalesce(status,'pending')) as status, count(*) as c")
            ->groupBy('y','m','status')
            ->get();

        $heatmap = [];
        foreach (['pending','approved','paid','partial','cancelled'] as $st) {
            $heatmap[$st] = array_fill(1, 12, 0);
        }
        foreach ($heatRows as $row) {
            $s = $row->status;
            $m = (int) $row->m;
            if (isset($heatmap[$s]) && $m >= 1 && $m <= 12) {
                $heatmap[$s][$m] = (int) $row->c;
            }
        }

        // ===== Collection forecast (upcoming due dates) =====
        $forecast = [];
        $forecastRows = DB::table('invoices')
            ->select('outstanding_total', $dueCol . ' as due')
            ->where('total', '>', 0)
            ->get();

        foreach ($forecastRows as $row) {
            $dueDate = $row->due ? Carbon::parse($row->due) : $today;
            $week    = $dueDate->startOfWeek()->format('Y-m-d');
            $amount  = (float) ($row->outstanding_total ?? 0);

            if (!isset($forecast[$week])) {
                $forecast[$week] = 0;
            }
            $forecast[$week] += $amount;
        }
        ksort($forecast);

        // ===== Recent Transactions (3 รายการล่าสุด) =====
        $dateSortCol = $dateCol ?? 'created_at';
        $recent = DB::table('invoices')
            ->select('id','customer_name','total','status',$dateSortCol.' as d')
            ->orderByDesc($dateSortCol)->limit(3)->get();

        // ===== Expenses Breakdown (กลุ่มตัวอย่าง)
        // ถ้าหนิงมีคอลัมน์ category ให้ groupBy('category') ได้เลย
        $breakdown = DB::table('invoices')
            ->selectRaw('CASE WHEN total<0 THEN "General" ELSE "Other" END as label, SUM(ABS(total)) as amount')
            ->groupBy('label')->orderByDesc('amount')->limit(6)->get();

        // ===== Quotations overview =====
        $quoteDateCol = Schema::hasTable('quotations')
            ? $this->pickDateColumn('quotations', ['issue_date','created_at'])
            : null;

        $quotations = collect();
        if ($quoteDateCol) {
            $quotations = DB::table('quotations')
                ->select('id','number','customer_name','status','total', $quoteDateCol.' as d')
                ->orderByDesc($quoteDateCol)
                ->limit(20)
                ->get();
        }

        $history = collect();
        if (Schema::hasTable('quotation_logs')) {
            $history = DB::table('quotation_logs as l')
                ->leftJoin('quotations as q', 'q.id', '=', 'l.quotation_id')
                ->select('l.id','l.action','l.description','l.user_name','l.created_at','q.number','q.customer_name')
                ->orderByDesc('l.created_at')
                ->limit(12)
                ->get();
        }

        return view('dashboard', [
            'chartMonths' => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
            'barData'     => $barData,
            'kpis'        => [
                'ar' => $accountsReceivable,
                'ap' => $accountsPayableAbs,
                'netProfit' => $netProfit,
                'closing'   => $closingBalance,
            ],
            'recent'      => $recent,
            'breakdown'   => $breakdown,
            'periodText'  => $fromY->format('M d, Y').' – '.$today->format('M d, Y'),
            'quotations'  => $quotations,
            'history'     => $history,
            'aging'       => $agingBuckets,
            'topCustomers'=> $topCustomers,
            'topProducts' => $topProducts,
            'marginRows'  => $marginRows,
            'heatmap'     => $heatmap,
            'forecast'    => $forecast,
        ]);
    }

    private function pickDateColumn(string $table, array $pref)
    {
        foreach ($pref as $c) if (Schema::hasColumn($table,$c)) return $c;
        return 'created_at';
    }

    private function monthExtract(string $driver, string $col)
    {
        return match($driver){
            'mysql'  => "DATE_FORMAT($col, '%m')",
            'pgsql'  => "TO_CHAR($col, 'MM')",
            default  => "strftime('%m', $col)", // sqlite & others
        };
    }

    private function yearExtract(string $driver, string $col)
    {
        return match($driver){
            'mysql'  => "DATE_FORMAT($col, '%Y')",
            'pgsql'  => "TO_CHAR($col, 'YYYY')",
            default  => "strftime('%Y', $col)",
        };
    }
}
