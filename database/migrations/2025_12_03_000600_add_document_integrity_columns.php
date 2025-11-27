<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['invoices', 'credit_notes', 'receipts', 'quotations'];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) use ($table) {
                if (!Schema::hasColumn($table, 'cancelled_at')) {
                    $table->timestamp('cancelled_at')->nullable()->after('updated_at');
                }

                if (!Schema::hasColumn($table, 'cancellation_reason')) {
                    $table->text('cancellation_reason')->nullable()->after('cancelled_at');
                }

                if (!Schema::hasColumn($table, 'status_before_cancellation')) {
                    $table->string('status_before_cancellation')->nullable()->after('cancellation_reason');
                }

                if (!Schema::hasColumn($table, 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        $tables = ['invoices', 'credit_notes', 'receipts', 'quotations'];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $table) use ($table) {
                if (Schema::hasColumn($table, 'cancelled_at')) {
                    $table->dropColumn('cancelled_at');
                }
                if (Schema::hasColumn($table, 'cancellation_reason')) {
                    $table->dropColumn('cancellation_reason');
                }
                if (Schema::hasColumn($table, 'status_before_cancellation')) {
                    $table->dropColumn('status_before_cancellation');
                }
                if (Schema::hasColumn($table, 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};
