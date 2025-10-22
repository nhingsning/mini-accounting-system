<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            $t->string('branch_name')->nullable()->after('office_type'); // ชื่อสาขา
            $t->string('branch_code')->nullable()->after('branch_name'); // รหัส/เลขที่สาขา
            $t->foreignId('head_office_id')->nullable()->after('branch_code')
                ->constrained('customers')->nullOnDelete(); // อ้างถึงสำนักงานใหญ่ (self relation)
        });
    }
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            $t->dropConstrainedForeignId('head_office_id');
            $t->dropColumn(['branch_name','branch_code']);
        });
    }
};
