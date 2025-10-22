<?php
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Models\Quotation;
use Carbon\Carbon;

Artisan::command('quotations:renumber {--apply}', function () {
    $apply = (bool) $this->option('apply');

    $all = Quotation::query()
        ->orderBy('issue_date')
        ->orderBy('created_at')
        ->get();

    if ($all->isEmpty()) {
        $this->warn('ไม่พบข้อมูล');
        return;
    }

    $groups = $all->groupBy(function ($q) {
        return Carbon::parse($q->issue_date ?? $q->created_at)->format('Y-m');
    });

    $rows = [];
    foreach ($groups as $ym => $qs) {
        $seq = 0;
        foreach ($qs->sortBy('created_at') as $q) {
            $seq++;
            $rows[] = [$q, 'QT'.$ym.'-'.str_pad($seq,4,'0',STR_PAD_LEFT)];
        }
    }

    if (!$apply) {
        $this->table(['ID','Old','→','New'], collect($rows)->map(fn($x)=>[$x[0]->id,$x[0]->number,'→',$x[1]])->all());
        $this->comment('พรีวิวเท่านั้น — ถ้าจะบันทึกจริงใส่ --apply');
        return;
    }

    DB::transaction(function () use ($rows) {
        foreach ($rows as [$q,$new]) {
            $q->number = $new;
            $q->save();
        }
    });

    $this->info('รีนัมเบอร์เรียบร้อย');
})->describe('รีนัมเบอร์เลข Quotation เป็น QTYYYY-MM-####');
