<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Carbon;
use App\Models\QuotationAttachment;
use App\Models\QuotationLog;

class Quotation extends Model
{
    protected $fillable = [
        'number','customer_id','customer_name','customer_address','customer_tax_id',
        'customer_branch_type','customer_branch_code','salesperson','reference',
        'issue_date','valid_until','currency','tax_rate','vat_mode','vat_enabled',
        'discount_percent','discount_amount','status','notes','payment_terms',
        'contact_name','contact_email','contact_phone',
        'subtotal','tax','total',
        'period','month_seq',
    ];

    // ใช้ 'date' พอ (ไม่ต้อง datetime) เพื่อให้ format('Y-m') ทำงานเนียน
    protected $casts = [
        'issue_date'  => 'date',
        'valid_until' => 'date',
        'vat_enabled' => 'boolean',
        'tax_rate'    => 'float',
        'discount_percent' => 'float',
        'discount_amount'  => 'float',
        'subtotal'    => 'float',
        'tax'         => 'float',
        'total'       => 'float',
        'month_seq'   => 'integer',
    ];

    /* ===================== Relations ===================== */

    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }

    // เพิ่ม relation ไปหา Invoice (ไว้ใช้สร้าง/ลิงก์ INV อัตโนมัติ)
    public function invoice()
    {
        return $this->hasOne(\App\Models\Invoice::class);
    }

    public function attachments()
    {
        return $this->hasMany(QuotationAttachment::class);
    }

    public function logs()
    {
        return $this->hasMany(QuotationLog::class);
    }

    /* ===================== Routing (URL) ===================== */

    /** ให้ URL ใช้ number เป็นค่าเริ่มต้น */
    public function getRouteKeyName(): string
    {
        return 'number';
    }

    /** รองรับทั้ง number และ id (กันลิงก์เก่า 404) */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field) {
            return parent::resolveRouteBinding($value, $field);
        }
        return static::query()
            ->where('number', $value)
            ->orWhere('id', $value)
            ->firstOrFail();
    }

    /* ===================== Mutators: รับรูปแบบวันที่หลายแบบ ===================== */

    // รับทั้ง Y-m-d และ d/m/Y จากฟอร์ม
    protected function issueDate(): Attribute
    {
        return Attribute::set(function ($value) {
            if (empty($value)) return null;
            if ($value instanceof Carbon) return $value;
            // ลอง parse d/m/Y ก่อน
            if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $value)) {
                return Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
            }
            return Carbon::parse($value)->startOfDay();
        });
    }

    protected function validUntil(): Attribute
    {
        return Attribute::set(function ($value) {
            if (empty($value)) return null;
            if ($value instanceof Carbon) return $value;
            if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $value)) {
                return Carbon::createFromFormat('d/m/Y', $value)->startOfDay();
            }
            return Carbon::parse($value)->startOfDay();
        });
    }

    /* ===================== Auto-numbering ===================== */

    protected static function booted()
    {
        // ออกเลขตอนสร้าง ถ้ายังไม่มี number
        static::creating(function (self $q) {
            if (empty($q->issue_date)) {
                $q->issue_date = now();
            }
            $q->assignNumberIfMissing();
        });

        // ถ้าเปลี่ยนเดือนของ issue_date → ออกเลขใหม่สำหรับเดือนนั้น
        static::updating(function (self $q) {
            if ($q->isDirty('issue_date')) {
                $old = static::toCarbon($q->getOriginal('issue_date'));
                $new = static::toCarbon($q->issue_date);
                if ($old && $new && $old->format('Y-m') !== $new->format('Y-m')) {
                    $q->assignNewNumberForCurrentPeriod();
                }
            }
        });

        // กันกรณีเซ็ต number เอง แต่ยังไม่ตั้ง period/seq
        static::saving(function (self $q) {
            if (!empty($q->number) && (empty($q->period) || empty($q->month_seq))) {
                $q->syncPeriodAndSeqFromNumber();
            }
        });
    }

    /** ออกเลขถ้ายังไม่มี */
    public function assignNumberIfMissing(): void
    {
        if (!empty($this->number)) {
            if (empty($this->period) || empty($this->month_seq)) {
                $this->syncPeriodAndSeqFromNumber();
            }
            return;
        }
        $this->assignNewNumberForCurrentPeriod();
    }

    /**
     * ออกเลขใหม่สำหรับเดือนของ issue_date ปัจจุบัน
     * รูปแบบ: QTYYYY-MM-####  (เช่น QT2025-10-0003)
     */
    public function assignNewNumberForCurrentPeriod(): void
    {
        $period = ($this->issue_date ?? now())->format('Y-m');
        $this->period = $period;

        // ลำดับล่าสุดของเดือนนั้น
        $start = (int) static::where('period', $period)->max('month_seq');
        $seq = max($start, 0) + 1;

        // กันชน: ขยับ seq ไปจนกว่าจะว่าง
        do {
            $candidate = sprintf('QT%s-%04d', $period, $seq);
            $exists = static::where('number', $candidate)
                ->when($this->exists, fn($q) => $q->where('id', '!=', $this->id))
                ->exists();

            if (!$exists) {
                $this->month_seq = $seq;
                $this->number    = $candidate;
                break;
            }
            $seq++;
        } while (true);
    }

    /** ดึง period/seq จาก number ถ้ารูปแบบถูกต้อง */
    protected function syncPeriodAndSeqFromNumber(): void
    {
        if (empty($this->number)) return;

        // คาดหวังรูปแบบ QTYYYY-MM-#### 
        if (preg_match('/^QT(\d{4}-\d{2})-(\d{4})$/', $this->number, $m)) {
            $this->period    = $this->period    ?: $m[1];
            $this->month_seq = $this->month_seq ?: (int) ltrim($m[2], '0');
        }
    }

    public static function previewNextNumber(?Carbon $date = null): string
    {
        $period = ($date ?? now())->format('Y-m');
        $start = (int) static::where('period', $period)->max('month_seq');
        $seq = max($start, 0) + 1;

        return sprintf('QT%s-%04d', $period, $seq);
    }

    /* ===================== Helpers ===================== */

    protected static function toCarbon($value): ?Carbon
    {
        if (empty($value)) return null;
        if ($value instanceof Carbon) return $value;
        try { return Carbon::parse($value); } catch (\Throwable) { return null; }
    }
public function customer(){ return $this->belongsTo(Customer::class); }
// ใน class Quotation
public function scopeSearch($q, ?string $term)
{
    if (!filled($term)) return $q;
    $term = trim($term);

    return $q->where(function ($qq) use ($term) {
        $qq->where('number', 'like', "%{$term}%")
           ->orWhere('customer_name', 'like', "%{$term}%")
           ->orWhere('status', 'like', "%{$term}%")
           ->orWhere('total', 'like', "%{$term}%");
    });
}

}
