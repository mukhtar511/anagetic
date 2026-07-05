<?php

use App\Models\ProductColor;

/* SPEC §4.6 — colour stock quantities are NEVER exposed to buyers. */

it('lists products without leaking colour qty', function () {
    $store = aStore();
    $product = aProduct($store, ['ready' => 950]);
    ProductColor::create(['product_id' => $product->id, 'name' => 'أسود', 'hex' => '#000', 'qty' => 7]);

    // List endpoint: no colours/qty at all in the card shape.
    $list = $this->getJson('/api/products')->assertOk();
    $json = json_encode($list->json());
    expect($json)->not->toContain('"qty"');

    // Detail endpoint: colours expose name/hex + in_stock boolean, never the qty.
    $detail = $this->getJson("/api/products/{$product->id}")->assertOk();
    $detail->assertJsonPath('data.colors.0.name', 'أسود')
        ->assertJsonPath('data.colors.0.in_stock', true);
    expect($detail->json('data.colors.0'))->not->toHaveKey('qty');
});
