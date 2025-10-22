<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Quotation;
use Carbon\Carbon;

class RenumberQuotations extends Command
{
    protected $signature = 'quotations:renumber 
                            {--apply : บันทึกจริง (ไม่ใส่ option = โหมดพรีวิว)}
                            {--from= : เริ่มที่เดือนปี (รูปแบบ YYYY-MM) }
                            {--to=   : ถึงเดือนปี (รูปแบบ YYYY-MM) }';

    protected $description = 'รีนัมเบอร์ใบเสนอราคาเป็นรูปแบบ QTYYYY-MM-#### โดยเรียงใหม่ตามเดือนของ issue_date';

    public function handle(): int
    {
        $apply = (bool)$this->option('apply');
        $from  = $this->option('from');
        $to    = $this->option('to');

        $this->info($apply ? '>> โหมดบันทึกจริง' : '>> โหมดพรีวิว (ไม่บันทึก)');

        $qry = Quotation::query()->orderBy('issue_date')->orderBy('created_at');

        // กรองช่วงเดือนถ้าระบุ
        if ($from) {
            $fromDate = Carbon::createFromFormat('Y-m', $from)->startOfMonth();
            $qry->where('issue_date', '>=', $fromDate);
        }
        if ($to) {
            $toDate = Carbon::createFromFormat('Y-m', $to)->endOfMonth();
            $qry->where('issue_date', '<=', $toDate);
        }

        $all = $qry->get();

        if ($all->isEmpty()) {
            $this->warn('ไม่พบข้อมูล');
            return self::SUCCESS;
        }

        // จัดกลุ่มตามปี-เดือนของ issue_date (ถ้าไม่มีใช้ created_at)
        $groups = $all->groupBy(function($q){
            return Carbon::parse($q->issue_date ?? $q->created_at)->format('Y-m');
        });

        $previewRows = [];

        $runner = function () use ($groups, &$previewRows) {
            foreach ($groups as $ym => $rows) {
                $seq = 0;
                foreach ($rows->sortBy('created_at') as $q) {
                    $seq++;
                    $new = 'QT'.$ym.'-'.str_pad($seq, 4, '0', STR_PAD_LEFT);
                    $previewRows[] = [$q->id, $q->number, '→', $new];
                    yield [$q, $new];
                }
            }
        };

        if (!$apply) {
            $this->table(['ID','Old','→','New'], $previewRows ?: iterator_to_array((function() use ($runner){
                $out=[];
                foreach ($runner() as [$q,$new]) $out[] = [$q->id,$q->number,'→',$new];
                return $out;
            })()));
            $this->comment('พรีวิวเท่านั้น — ถ้าจะบันทึกจริงให้ใส่ --apply');
            return self::SUCCESS;
        }

        // บันทึกจริง (ในทรานแซคชัน)
        DB::transaction(function () use ($runner) {
            foreach ($runner() as [$q, $new]) {
                $q->number = $new;
                $q->save();
            }
        });

        $this->info('รีนัมเบอร์เรียบร้อย');
        return self::SUCCESS;
    }
}
