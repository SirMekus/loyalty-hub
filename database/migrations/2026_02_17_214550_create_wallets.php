<?php

use App\Enums\Currency;
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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->morphs('owner');
            $table->string('wallet_address')->nullable();
            $table->string('currency')->default(Currency::NGN->value);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_transaction_id')->nullable()->constrained('wallet_transactions')->cascadeOnDelete();
            $table->string('currency')->default(Currency::NGN->value);
            $table->unsignedBigInteger('amount');
            $table->bigInteger('opening_balance')->nullable();
            $table->bigInteger('closing_balance')->nullable();
            $table->bigInteger('balance')->default(0);
            $table->bigInteger('fee')->nullable();
            $table->bigInteger('commission')->default(0);
            $table->bigInteger('net_balance')->nullable();
            $table->foreignId('order_id')->nullable()->constrained();
            $table->string('description')->nullable();
            $table->string('transaction_type')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
