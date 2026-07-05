<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('owner');   // CouponOwner: platform|store
            $table->foreignId('store_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('kind');    // CouponKind: fix|pct
            $table->decimal('value', 10, 2);
            $table->decimal('cap', 10, 2)->nullable();      // max discount for pct
            $table->decimal('min', 10, 2)->default(0);      // minimum order
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('active')->default(true);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->unique(['owner', 'store_id', 'code']);
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('discount', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
        Schema::dropIfExists('coupons');
    }
};
