<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // An order groups items from ONE seller. A multi-seller checkout creates a
        // parent order + one child order per seller (own shipment/code/escrow). SPEC §4.1 / D-06.
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();               // e.g. A-1043 / A-1043-B
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('parent_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('status')->default('pending_payment'); // OrderStatus
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('deposit_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->boolean('is_custom')->default(false);
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('smart_request_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->boolean('cancellable')->default(true);
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('mode');                          // ProductMode
            $table->string('color')->nullable();
            $table->foreignId('size_profile_id')->nullable()->constrained('size_profiles')->nullOnDelete();
            $table->json('measurements')->nullable();
            $table->text('notes')->nullable();
            $table->json('addons')->nullable();              // [{name,price}]
            $table->string('packaging')->nullable();
            $table->decimal('packaging_price', 10, 2)->default(0);
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('unit_price', 10, 2);            // base (incl. custom) per unit
            $table->decimal('addons_total', 10, 2)->default(0);
            $table->decimal('deposit', 10, 2)->default(0);
            $table->decimal('line_total', 10, 2);
            $table->string('delivery_code', 8)->nullable();  // 4-digit, per shipment
            $table->timestamps();
        });

        // Escrow ledger — funds held until the delivery code releases them. SPEC §4.1.
        Schema::create('escrow_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->decimal('held_amount', 10, 2);
            $table->decimal('coupon_discount', 10, 2)->default(0); // seller coupon (reduces commission base)
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2)->default(0);
            $table->string('status')->default('held');       // held|released|refunded
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        // Rental calendar — auto-blocks rental + return + inspection. SPEC §4.3.
        Schema::create('rental_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->date('occasion_date');
            $table->date('block_from');
            $table->date('block_to');
            $table->date('inspection_day');
            $table->string('status')->default('booked');     // RentalStatus
            $table->timestamps();
        });

        // Free-return requests. SPEC §4.2.
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('reason');
            $table->string('status')->default('requested');  // requested|pickup|refunded
            $table->json('timeline')->nullable();
            $table->timestamps();
        });

        // Damage / commercial disputes resolved by admin. SPEC §7.
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->string('type');                          // rental_damage|chat_report|...
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('deposit_id')->nullable();
            $table->json('evidence')->nullable();            // delivery/return photos
            $table->string('decision')->nullable();
            $table->decimal('cut_value', 10, 2)->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('open');       // open|resolved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('returns');
        Schema::dropIfExists('rental_bookings');
        Schema::dropIfExists('escrow_transactions');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
