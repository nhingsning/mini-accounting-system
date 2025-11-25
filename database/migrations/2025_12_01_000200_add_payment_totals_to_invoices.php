<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'paid_total')) {
                $table->decimal('paid_total', 12, 2)->default(0)->after('total');
            }
            if (!Schema::hasColumn('invoices', 'outstanding_total')) {
                $table->decimal('outstanding_total', 12, 2)->default(0)->after('paid_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'paid_total')) {
                $table->dropColumn('paid_total');
            }
            if (Schema::hasColumn('invoices', 'outstanding_total')) {
                $table->dropColumn('outstanding_total');
            }
        });
    }
};
