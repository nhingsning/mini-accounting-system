<?php
// database/migrations/2025_10_21_000001_add_customer_id_to_qt_inv.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // quotations
        Schema::table('quotations', function (Blueprint $t) {
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete()->index();
            // ถ้ายังมีคอลัมน์ customer_name เดิมอยู่ ให้คงไว้เพื่อแสดงผล/ย้อนหลังได้
        });

        // invoices
        Schema::table('invoices', function (Blueprint $t) {
            $t->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete()->index();
        });
    }

    public function down(): void {
        Schema::table('quotations', function (Blueprint $t) {
            $t->dropConstrainedForeignId('customer_id');
        });
        Schema::table('invoices', function (Blueprint $t) {
            $t->dropConstrainedForeignId('customer_id');
        });
    }
};
