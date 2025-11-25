<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('invoice_id');
                $table->decimal('amount', 12, 2);
                $table->string('currency', 10)->nullable();
                $table->string('method', 50)->nullable();
                $table->string('reference')->nullable();
                $table->text('note')->nullable();
                $table->dateTime('paid_at')->nullable();
                $table->string('status', 30)->default('cleared');
                $table->string('slip_path')->nullable();
                $table->unsignedBigInteger('bank_statement_id')->nullable();
                $table->timestamps();

                $table->index('invoice_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
