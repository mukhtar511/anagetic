<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One unified wallet per user (buyer income + seller payouts). SPEC §3.7.
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance', 10, 2)->default(0);
            $table->string('iban')->nullable();
            $table->string('bank_name')->nullable();
            $table->timestamps();
        });

        // Every money movement is a ledger row — the wallet balance is their sum. SPEC §6.
        Schema::create('wallet_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('type');           // sale_income|deposit_refund|rental_income|refund|withdrawal|payment|...
            $table->string('ref')->nullable(); // order code, etc.
            $table->string('text');            // Arabic ledger line (verbatim style from prototype)
            $table->decimal('amount', 10, 2);  // signed
            $table->timestamps();
        });

        // Rental security deposits, held by the platform (not the seller). SPEC §4.3.
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('held'); // DepositStatus
            $table->decimal('cut_amount', 10, 2)->nullable();
            $table->string('cut_reason')->nullable();
            $table->string('deposit_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
        Schema::dropIfExists('wallet_ledger');
        Schema::dropIfExists('wallets');
    }
};
