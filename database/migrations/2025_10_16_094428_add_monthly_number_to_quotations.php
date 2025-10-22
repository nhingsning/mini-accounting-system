<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_monthly_number_to_quotations.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('quotations', function (Blueprint $table) {
            // เพิ่มเฉพาะคอลัมน์ที่ยังไม่มี
            if (!Schema::hasColumn('quotations', 'number')) {
                $table->string('number')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('quotations', 'period')) {
                $table->string('period', 7)->nullable()->index()->after('number'); // YYYY-MM
            }
            if (!Schema::hasColumn('quotations', 'month_seq')) {
                $table->unsignedInteger('month_seq')->nullable()->after('period');
            }

            if (!Schema::hasColumn('quotations', 'issue_date')) {
                $table->date('issue_date')->nullable()->after('customer_name');
            }
        });
    }

    public function down(): void {
        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations','number'))    $table->dropColumn('number');
            if (Schema::hasColumn('quotations','period'))    $table->dropColumn('period');
            if (Schema::hasColumn('quotations','month_seq')) $table->dropColumn('month_seq');
            // ไม่แตะ issue_date ถ้ามีของเดิมใช้อยู่
        });
    }
};
