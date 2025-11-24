<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name');
            $table->text('customer_address')->nullable();
            $table->string('customer_tax_id')->nullable();
            $table->string('customer_branch_type')->nullable();
            $table->string('customer_branch_code')->nullable();
            $table->date('issue_date')->nullable();
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status')->default('draft');
            $table->string('currency')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
