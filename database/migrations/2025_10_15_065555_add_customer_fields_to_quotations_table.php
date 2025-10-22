<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('quotations', 'customer_address')) {
                $table->string('customer_address')->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('quotations', 'customer_tax_id')) {
                $table->string('customer_tax_id', 32)->nullable()->after('customer_address');
            }
            if (!Schema::hasColumn('quotations', 'customer_branch_type')) {
                $table->enum('customer_branch_type', ['head', 'branch'])->nullable()->after('customer_tax_id');
            }
            if (!Schema::hasColumn('quotations', 'customer_branch_code')) {
                $table->string('customer_branch_code', 20)->nullable()->after('customer_branch_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            // แยก drop ทีละคอลัมน์ให้ชัวร์กับ SQLite
            foreach (['customer_address', 'customer_tax_id', 'customer_branch_type', 'customer_branch_code'] as $col) {
                if (Schema::hasColumn('quotations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
