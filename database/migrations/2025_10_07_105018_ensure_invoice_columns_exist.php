<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // invoices
        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $t) {
                Schema::hasColumn('invoices', 'number')        ?: $t->string('number')->nullable();
                Schema::hasColumn('invoices', 'customer_name') ?: $t->string('customer_name');
                Schema::hasColumn('invoices', 'issue_date')    ?: $t->date('issue_date');
                Schema::hasColumn('invoices', 'due_date')      ?: $t->date('due_date')->nullable();
                Schema::hasColumn('invoices', 'tax_rate')      ?: $t->decimal('tax_rate',5,2)->default(0);
                Schema::hasColumn('invoices', 'subtotal')      ?: $t->decimal('subtotal',12,2)->default(0);
                Schema::hasColumn('invoices', 'tax')           ?: $t->decimal('tax',12,2)->default(0);
                Schema::hasColumn('invoices', 'total')         ?: $t->decimal('total',12,2)->default(0);
                Schema::hasColumn('invoices', 'status')        ?: $t->string('status')->default('unpaid');
            });
        }

        // invoice_items
        if (Schema::hasTable('invoice_items')) {
            Schema::table('invoice_items', function (Blueprint $t) {
                if (!Schema::hasColumn('invoice_items','invoice_id')) {
                    $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                }
                Schema::hasColumn('invoice_items','description') ?: $t->string('description');
                Schema::hasColumn('invoice_items','qty')         ?: $t->integer('qty')->default(1);
                Schema::hasColumn('invoice_items','price')       ?: $t->decimal('price',12,2)->default(0);
                Schema::hasColumn('invoice_items','line_total')  ?: $t->decimal('line_total',12,2)->default(0);
            });
        }
    }

    public function down(): void
    {
        // ปล่อยว่างไว้ก็ได้ (ไม่จำเป็นต้องย้อน)
    }
};
