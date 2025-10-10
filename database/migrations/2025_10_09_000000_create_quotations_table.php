<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();              // เช่น QT20251009-0001
            $table->string('customer_name');
            $table->dateTime('issue_date');
            $table->dateTime('valid_until')->nullable();     // ใบเสนอราคาหมดอายุเมื่อไหร่
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('status')->default('draft');      // draft|sent|accepted|expired
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
