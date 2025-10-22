<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->string('name');                        // *Customer Name
            $t->string('name_private')->nullable();    // Customer Name (no show)
            $t->string('tax_id', 32)->nullable();
            $t->string('tel', 64)->nullable();
            $t->string('fax', 64)->nullable();
            $t->string('payment_terms', 128)->nullable();

            $t->text('address_show')->nullable();      // *Address (แสดงในเอกสาร)
            $t->text('address_private')->nullable();   // Address (no show)

            $t->enum('office_type',['head','branch'])->default('head'); // Head/Branch
            $t->timestamps();

            $t->index(['name','tax_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
