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
        Schema::create('outgoing_mail_settings', function (Blueprint $table) {
            $table->id();
            $table->string('host')->default('smtp.hostinger.com');
            $table->unsignedSmallInteger('port')->default(465);
            $table->string('encryption', 10)->default('ssl');
            $table->string('username');
            $table->text('password')->nullable();
            $table->string('from_address');
            $table->string('from_name')->nullable();
            $table->string('reply_to')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outgoing_mail_settings');
    }
};
