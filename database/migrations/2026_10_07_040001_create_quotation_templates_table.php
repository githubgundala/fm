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
        Schema::create('quotation_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('project_type')->nullable();
            $table->string('default_title')->nullable();
            $table->text('default_summary')->nullable();
            $table->json('default_scope')->nullable();
            $table->text('default_terms')->nullable();
            $table->text('default_exclusions')->nullable();
            $table->text('default_notes')->nullable();
            $table->integer('default_validity_days')->default(3);
            $table->decimal('default_tax_rate', 5, 2)->nullable()->default(11);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_templates');
    }
};
