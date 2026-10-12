<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoice_settings', function (Blueprint $table) {
            $table->string('quotation_prefix')->default('Q')->after('payment_prefix');
            $table->integer('default_quotation_validity_days')->default(3)->after('default_due_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_settings', function (Blueprint $table) {
            $table->dropColumn(['quotation_prefix', 'default_quotation_validity_days']);
        });
    }
};
