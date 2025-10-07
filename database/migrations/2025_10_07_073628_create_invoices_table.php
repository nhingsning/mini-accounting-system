<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->string('number')->nullable();
            $t->string('customer_name');
            $t->date('issue_date');
            $t->date('due_date')->nullable();
            $t->decimal('tax_rate', 5, 2)->default(0);
            $t->decimal('subtotal', 12, 2)->default(0);
            $t->decimal('tax', 12, 2)->default(0);
            $t->decimal('total', 12, 2)->default(0);
            $t->string('status')->default('unpaid');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
