<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            // เพิ่มคอลัมน์หน่วย (เช่น ชิ้น, กล่อง, ชั่วโมง)
            $table->string('unit', 50)->nullable()->after('qty');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
