<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            // อัตราส่วนลด (%)
            if (!Schema::hasColumn('quotations','discount_rate')) {
                $table->decimal('discount_rate', 8, 2)->default(0)->after('subtotal');
            }
            // อัตราหัก ณ ที่จ่าย (%)
            if (!Schema::hasColumn('quotations','wht_rate')) {
                $table->decimal('wht_rate', 8, 2)->default(0)->after('tax_rate');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (Schema::hasColumn('quotations','discount_rate')) $table->dropColumn('discount_rate');
            if (Schema::hasColumn('quotations','wht_rate')) $table->dropColumn('wht_rate');
        });
    }
};
