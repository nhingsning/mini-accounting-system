<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            // กันพังถ้าคอลัมน์ยังไม่มี
            if (Schema::hasColumn('quotations', 'number')) {
                $table->index('number');
            }
            if (Schema::hasColumn('quotations', 'customer_name')) {
                $table->index('customer_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            // ชื่อ index แบบง่ายของ Laravel (drop โดยใส่คอลัมน์เป็นอาเรย์)
            if (Schema::hasColumn('quotations', 'number')) {
                $table->dropIndex(['number']);
            }
            if (Schema::hasColumn('quotations', 'customer_name')) {
                $table->dropIndex(['customer_name']);
            }
        });
    }
};
