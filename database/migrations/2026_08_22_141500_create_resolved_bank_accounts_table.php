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
        Schema::create('resolved_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_number');
            $table->string('bank_code');
            $table->string('account_name');
            $table->timestamps();

            $table->unique(['account_number', 'bank_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resolved_bank_accounts');
    }
};
