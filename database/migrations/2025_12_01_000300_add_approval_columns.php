<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('invoices', 'approval_status')) {
                    $table->string('approval_status')->default('draft')->after('status');
                }
                if (!Schema::hasColumn('invoices', 'approval_step')) {
                    $table->unsignedInteger('approval_step')->default(0)->after('approval_status');
                }
            });
        }

        if (Schema::hasTable('credit_notes')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                if (!Schema::hasColumn('credit_notes', 'approval_status')) {
                    $table->string('approval_status')->default('draft')->after('status');
                }
                if (!Schema::hasColumn('credit_notes', 'approval_step')) {
                    $table->unsignedInteger('approval_step')->default(0)->after('approval_status');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'role')) {
                    $table->string('role')->default('drafter')->after('email');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (Schema::hasColumn('invoices', 'approval_status')) {
                    $table->dropColumn('approval_status');
                }
                if (Schema::hasColumn('invoices', 'approval_step')) {
                    $table->dropColumn('approval_step');
                }
            });
        }

        if (Schema::hasTable('credit_notes')) {
            Schema::table('credit_notes', function (Blueprint $table) {
                if (Schema::hasColumn('credit_notes', 'approval_status')) {
                    $table->dropColumn('approval_status');
                }
                if (Schema::hasColumn('credit_notes', 'approval_step')) {
                    $table->dropColumn('approval_step');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'role')) {
                    $table->dropColumn('role');
                }
            });
        }
    }
};
