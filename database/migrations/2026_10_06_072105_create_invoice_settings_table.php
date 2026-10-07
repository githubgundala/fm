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
        Schema::create('invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('PT Contoh Sejahtera');
            $table->text('company_address')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_email')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('invoice_prefix')->default('INV');
            $table->string('payment_prefix')->default('PAY');
            $table->boolean('yearly_reset')->default(true);
            $table->integer('default_due_days')->default(14);
            $table->decimal('default_tax_rate', 5, 2)->nullable();
            $table->text('default_terms')->nullable();
            $table->text('default_notes')->nullable();
            $table->string('email_subject_template')->default('Invoice {invoice_number} dari {company_name}');
            $table->text('email_body_template')->nullable();
            $table->boolean('allow_overpayment')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_settings');
    }
};
