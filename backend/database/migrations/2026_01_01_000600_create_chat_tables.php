<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Order-bound conversations. "payfirst" stores stay locked until payment. SPEC §3.10 / §4.8.
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->boolean('locked')->default(false);
            $table->timestamps();
            $table->unique(['buyer_id', 'store_id', 'order_id']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('text'); // text|addon_offer|system
            $table->text('body');
            $table->boolean('flagged')->default(false); // external-dealing filter. SPEC §4.10
            $table->timestamps();
        });

        // Formal in-chat addition offers that attach to the order on approval. SPEC §3.10.
        Schema::create('addon_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->string('status')->default('pending'); // pending|accepted|rejected
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addon_offers');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};
