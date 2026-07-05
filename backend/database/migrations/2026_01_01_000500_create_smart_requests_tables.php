<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reverse marketplace: buyer posts a need, sellers bid. SPEC §3.9 / §4.7.
        Schema::create('smart_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');         // buy|rent|both|custom
            $table->text('description');
            $table->string('category');
            $table->string('size')->nullable();
            $table->decimal('budget', 10, 2);
            $table->date('need_by')->nullable();
            $table->string('scope')->default('region_first'); // region_first|region_only|all
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->json('ref_images')->nullable();
            $table->text('change_notes')->nullable();
            $table->string('status')->default('open'); // SmartRequestStatus
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('smart_request_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('smart_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 2);
            $table->text('message')->nullable();
            $table->string('delivery_note')->nullable();
            $table->string('status')->default('sent'); // sent|accepted|rejected|hidden
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('smart_request_offers');
        Schema::dropIfExists('smart_requests');
    }
};
