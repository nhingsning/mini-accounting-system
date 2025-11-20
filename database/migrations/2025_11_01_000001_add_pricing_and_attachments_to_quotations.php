<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            if (!Schema::hasColumn('quotations', 'currency')) {
                $table->string('currency', 10)->nullable()->after('status');
            }
            if (!Schema::hasColumn('quotations', 'discount_percent')) {
                $table->decimal('discount_percent', 8, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('quotations', 'discount_amount')) {
                $table->decimal('discount_amount', 12, 2)->default(0)->after('discount_percent');
            }
            if (!Schema::hasColumn('quotations', 'vat_mode')) {
                $table->string('vat_mode', 16)->default('exclusive')->after('tax_rate');
            }
            if (!Schema::hasColumn('quotations', 'vat_enabled')) {
                $table->boolean('vat_enabled')->default(true)->after('vat_mode');
            }
            if (!Schema::hasColumn('quotations', 'reference')) {
                $table->string('reference')->nullable()->after('salesperson');
            }
            if (!Schema::hasColumn('quotations', 'payment_terms')) {
                $table->string('payment_terms')->nullable()->after('reference');
            }
            if (!Schema::hasColumn('quotations', 'contact_name')) {
                $table->string('contact_name')->nullable()->after('payment_terms');
            }
            if (!Schema::hasColumn('quotations', 'contact_email')) {
                $table->string('contact_email')->nullable()->after('contact_name');
            }
            if (!Schema::hasColumn('quotations', 'contact_phone')) {
                $table->string('contact_phone')->nullable()->after('contact_email');
            }
        });

        Schema::table('quote_items', function (Blueprint $table) {
            if (!Schema::hasColumn('quote_items', 'discount')) {
                $table->decimal('discount', 12, 2)->default(0)->after('price');
            }
        });

        if (!Schema::hasTable('quotation_attachments')) {
            Schema::create('quotation_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quotation_id')->constrained('quotations')->cascadeOnDelete();
                $table->string('path');
                $table->string('original_name');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            foreach ([
                'currency','discount_percent','discount_amount','vat_mode','vat_enabled',
                'reference','payment_terms','contact_name','contact_email','contact_phone'
            ] as $col) {
                if (Schema::hasColumn('quotations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('quote_items', function (Blueprint $table) {
            if (Schema::hasColumn('quote_items', 'discount')) {
                $table->dropColumn('discount');
            }
        });

        Schema::dropIfExists('quotation_attachments');
    }
};
