<?php

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductSaleMode;
use App\Models\Region;
use App\Models\Store;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Feature tests hit the DB; Unit tests are pure (no RefreshDatabase).
uses(TestCase::class, RefreshDatabase::class)->in('Feature');
uses(TestCase::class)->in('Unit');

/*
 * Lightweight domain builders — enough to exercise the §4 business rules
 * without pulling in full factories.
 */

function aRegion(string $name = 'الرياض'): Region
{
    return Region::firstOrCreate(['name' => $name]);
}

function aUser(array $attrs = []): User
{
    $region = aRegion();

    $user = User::create(array_merge([
        'name' => 'نوف',
        'phone' => '5'.fake()->numerify('########'),
        'region_id' => $region->id,
        'phone_verified_at' => now(),
    ], $attrs));

    Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);

    return $user;
}

function aStore(array $attrs = []): Store
{
    $owner = $attrs['user'] ?? aUser(['name' => 'دار نورة']);
    unset($attrs['user']);

    return Store::create(array_merge([
        'user_id' => $owner->id,
        'name' => 'دار نورة للعبايات',
        'slug' => 'dar-noura-'.uniqid(),
        'region_id' => aRegion()->id,
        'status' => 'open',
        'comm_mode' => 'chat',
        'delivery_flat_price' => 25,
        'packaging' => [
            ['key' => 'normal', 'name' => 'تغليف عادي', 'price' => 0, 'enabled' => true],
            ['key' => 'special', 'name' => 'تغليف سبيشل', 'price' => 45, 'enabled' => true],
        ],
    ], $attrs));
}

/**
 * A product with the given modes. $modes: ['ready'=>950, 'custom'=>1100, 'rent'=>[140,250]].
 */
function aProduct(Store $store, array $modes = ['ready' => 950], array $attrs = []): Product
{
    $product = Product::create(array_merge([
        'store_id' => $store->id,
        'category' => 'abaya',
        'title' => 'عباية مطرزة بخيوط ذهبية',
        'status' => 'live',
        'region_id' => $store->region_id,
        'return_policy_ack' => true,
    ], $attrs));

    foreach ($modes as $type => $val) {
        ProductSaleMode::create([
            'product_id' => $product->id,
            'type' => $type,
            'price' => is_array($val) ? $val[0] : $val,
            'deposit' => is_array($val) ? ($val[1] ?? null) : null,
        ]);
    }

    return $product->load(['modes', 'store', 'addons', 'colors']);
}

function aStoreCoupon(Store $store, array $attrs = []): Coupon
{
    return Coupon::create(array_merge([
        'owner' => 'store',
        'store_id' => $store->id,
        'code' => 'NOURA50',
        'kind' => 'fix',
        'value' => 50,
        'min' => 500,
        'active' => true,
    ], $attrs));
}
