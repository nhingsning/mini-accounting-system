<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'quotation_id')) {
                $table->foreignId('quotation_id')->nullable()->constrained('quotations')->nullOnDelete()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'quotation_id')) {
                $table->dropConstrainedForeignId('quotation_id');
            }
        });
    }
};
