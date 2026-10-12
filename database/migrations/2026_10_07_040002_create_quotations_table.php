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
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->foreignId('template_id')->nullable()->constrained('quotation_templates')->nullOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('title')->nullable();
            $table->text('project_summary')->nullable();
            $table->date('issue_date');
            $table->date('valid_until');
            $table->string('status')->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->nullable();
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->string('currency', 3)->default('IDR');
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->text('exclusions')->nullable();
            $table->json('sender_snapshot')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('receiver_name')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
