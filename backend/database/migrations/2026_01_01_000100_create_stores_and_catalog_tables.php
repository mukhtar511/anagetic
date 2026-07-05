<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A user's storefront. Selling is enabled once the store profile + verification exist. SPEC §2.
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('bio')->nullable();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('status')->default('open');           // StoreStatus
            $table->string('comm_mode')->default('chat');         // CommMode
            $table->boolean('phone_visible')->default(false);
            $table->string('phone', 20)->nullable();
            $table->json('packaging')->nullable();                // [{key,name,price,enabled,image}]
            $table->unsignedInteger('delivery_free_km')->default(15);
            $table->decimal('delivery_flat_price', 10, 2)->default(25);
            $table->string('verification_status')->default('pending'); // pending|verified|rejected
            $table->json('verification_docs')->nullable();
            $table->unsignedSmallInteger('joined_year')->nullable();
            // Denormalised trust signals (computed, read-only). SPEC D-11.
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('completed_orders')->default(0);
            $table->unsignedTinyInteger('on_time_rate')->default(0); // %
            $table->timestamps();
        });

        // Buyer measurement profiles — the 7 fields. SPEC §3.2.
        Schema::create('size_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('shoulder', 6, 2)->nullable();
            $table->decimal('chest', 6, 2)->nullable();
            $table->decimal('waist', 6, 2)->nullable();
            $table->decimal('hip', 6, 2)->nullable();
            $table->decimal('upper_arm', 6, 2)->nullable();
            $table->decimal('sleeve_length', 6, 2)->nullable();
            $table->decimal('full_length', 6, 2)->nullable();
            $table->string('unit')->default('cm'); // cm|inch
            $table->string('photo')->nullable();
            $table->string('label')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('category');       // abaya|dress|care|acc|nobrand|used
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('condition')->default('new'); // new|used_excellent|used_verygood|used_good
            $table->string('code')->nullable();          // e.g. A2605
            $table->string('collection')->nullable();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('status')->default('live');   // live|out|draft
            $table->boolean('is_single_piece')->default(false);
            $table->json('images')->nullable();
            $table->json('packaging_override')->nullable();
            $table->string('comm_mode_override')->nullable();
            $table->decimal('original_price', 10, 2)->nullable(); // strike-through for used
            $table->boolean('return_policy_ack')->default(false); // mandatory ack at publish. SPEC §3.4
            $table->timestamps();
        });

        // Sale-mode rows: ready / custom / rent, each with its own price. SPEC §5.
        Schema::create('product_modes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // ProductMode: ready|custom|rent
            $table->decimal('price', 10, 2);
            $table->decimal('deposit', 10, 2)->nullable(); // rent only
            $table->string('prep_time')->nullable();
            $table->string('exec_time')->nullable();
            $table->string('rent_scope')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'type']);
        });

        // Colors carry a quantity that is NEVER exposed to buyers. SPEC §4.6.
        Schema::create('product_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('hex')->nullable();
            $table->unsignedInteger('qty')->default(0);
            $table->timestamps();
        });

        Schema::create('product_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->timestamps();
        });

        // Paid featured placements. SPEC §4.4.
        Schema::create('listings_featured', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('scope');  // FeaturedScope: region|all
            $table->unsignedTinyInteger('days');
            $table->decimal('price', 10, 2);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status')->default('active'); // FeaturedStatus
            $table->boolean('expiring_notified')->default(false);
            $table->timestamps();
        });

        Schema::create('store_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('city');
            $table->decimal('delivery_same_city', 10, 2)->default(15);
            $table->decimal('delivery_other', 10, 2)->default(30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_branches');
        Schema::dropIfExists('listings_featured');
        Schema::dropIfExists('product_addons');
        Schema::dropIfExists('product_colors');
        Schema::dropIfExists('product_modes');
        Schema::dropIfExists('products');
        Schema::dropIfExists('size_profiles');
        Schema::dropIfExists('stores');
    }
};
