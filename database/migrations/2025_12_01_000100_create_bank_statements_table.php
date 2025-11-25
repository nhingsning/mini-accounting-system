<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bank_statements')) {
            Schema::create('bank_statements', function (Blueprint $table) {
                $table->id();
                $table->string('description')->nullable();
                $table->string('reference')->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 10)->nullable();
                $table->date('transaction_date')->nullable();
                $table->string('status', 30)->default('unmatched');
                $table->unsignedBigInteger('matched_invoice_id')->nullable();
                $table->unsignedBigInteger('matched_payment_id')->nullable();
                $table->string('source_file')->nullable();
                $table->timestamps();

                $table->index(['status', 'transaction_date']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statements');
    }
};
