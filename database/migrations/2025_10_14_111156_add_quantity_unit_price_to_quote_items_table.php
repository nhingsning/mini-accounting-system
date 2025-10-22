<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            // ถ้าเคยมีอยู่แล้วจะไม่สร้างซ้ำ (กัน error เวลา migrate รอบต่อไป)
            if (!Schema::hasColumn('quote_items', 'quantity')) {
                $table->decimal('quantity', 12, 2)->default(0);
            }
            if (!Schema::hasColumn('quote_items', 'unit_price')) {
                $table->decimal('unit_price', 12, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            if (Schema::hasColumn('quote_items', 'quantity'))   $table->dropColumn('quantity');
            if (Schema::hasColumn('quote_items', 'unit_price')) $table->dropColumn('unit_price');
        });
    }
};
