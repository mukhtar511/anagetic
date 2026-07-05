<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ratings — completed orders only; feed the store trust index. SPEC §4.9.
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('stars');
            $table->json('chips')->nullable();
            $table->text('text')->nullable();
            $table->string('verified_type'); // purchase|rental
            $table->timestamps();
            $table->unique('order_id');
        });

        Schema::create('notifications_center', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ic')->nullable();
            $table->string('title');
            $table->string('body');
            $table->string('go')->nullable(); // deep-link target view
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('label');
            $table->string('district')->nullable();
            $table->text('details')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->string('code', 8);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
            $table->index('phone');
        });

        Schema::create('appeal_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();  // APL-*
            $table->foreignId('deposit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('open'); // open|reviewing|resolved
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution')->nullable();
            $table->timestamps();
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->boolean('notify_when_open')->default(false); // paused-store alert. SPEC §3.11
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        // Server-side cart.
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('mode');
            $table->string('color')->nullable();
            $table->json('measurements')->nullable();
            $table->text('notes')->nullable();
            $table->json('addons')->nullable();
            $table->string('packaging')->nullable();
            $table->decimal('packaging_price', 10, 2)->default(0);
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('deposit', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('appeal_tickets');
        Schema::dropIfExists('otp_codes');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('notifications_center');
        Schema::dropIfExists('ratings');
    }
};
