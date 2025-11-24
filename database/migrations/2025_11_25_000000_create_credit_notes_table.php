<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('credit_notes')) {
            Schema::create('credit_notes', function (Blueprint $table) {
                $table->id();
                $table->string('number')->nullable()->unique();
                $table->unsignedBigInteger('invoice_id')->nullable();
                $table->string('invoice_number')->nullable();
                $table->enum('type', ['credit', 'debit'])->default('credit');
                $table->string('status')->default('draft');
                $table->date('issue_date')->nullable();
                $table->string('customer_name')->nullable();
                $table->text('customer_address')->nullable();
                $table->string('customer_tax_id')->nullable();
                $table->string('customer_branch_type')->nullable();
                $table->string('customer_branch_code')->nullable();
                $table->text('reason')->nullable();
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->string('currency')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('credit_note_items')) {
            Schema::create('credit_note_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('credit_note_id');
                $table->string('description')->nullable();
                $table->decimal('qty', 12, 2)->default(1);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('line_total', 12, 2)->default(0);
                $table->string('unit')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
        Schema::dropIfExists('credit_notes');
    }
};
