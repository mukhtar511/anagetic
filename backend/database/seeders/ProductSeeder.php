<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductColor;
use App\Models\ProductSaleMode;
use App\Models\Store;
use Illuminate\Database\Seeder;

/**
 * The 10 demo products from SPEC §7 (PRODUCTS), each with its sale modes,
 * colors (hidden quantities) and priced addons — reproduced literally.
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $storeBySlug = Store::pluck('id', 'slug');

        // slug => [store slug, category, title, code, collection, condition,
        //          original_price, is_single_piece, status]
        $products = [
            'dress1' => ['reem-couture', 'dress', 'فستان سهرة ساتان بقصّة ملكية', 'A2605', 'مجموعة عيد ٢٠٢٦', 'new', null, false, 'live'],
            'abaya1' => ['dar-noura', 'abaya', 'عباية مناسبات مطرزة بخيوط ذهبية', 'N-114', null, 'new', null, false, 'live'],
            'rent1' => ['dar-noura', 'abaya', 'عباية زفّة مطرزة بالكامل — قطعة واحدة', 'N-201', null, 'new', null, true, 'live'],
            'noura3' => ['dar-noura', 'abaya', 'عباية كريب يابانية بقصّة كلوش', 'N-087', null, 'new', null, false, 'live'],
            'noura4' => ['dar-noura', 'abaya', 'فصّلي عبايتك على مقاسك', 'N-300', null, 'new', null, false, 'live'],
            'care1' => ['ward-aljori', 'care', 'كريم الورد الطائفي للبشرة', 'W-12', '٥٠ مل', 'new', null, false, 'live'],
            'acc1' => ['jawaher-hind', 'acc', 'طقم إكسسوارات بحجر العقيق', 'J-55', null, 'new', null, false, 'live'],
            'used1' => ['khazanat-sara', 'used', 'فستان زفاف لُبس مرة واحدة', 'S-09', null, 'used_excellent', 4500, false, 'live'],
            'nobrand1' => ['makhzoun-musammimat', 'nobrand', 'عبايات كريب أسود — دفعة ٥ قطع', 'B-33', null, 'new', null, false, 'live'],
            'used2' => ['khazanat-anoud', 'used', 'حقيبة سهرة مرصّعة', 'E-21', null, 'used_good', 750, false, 'out'],
        ];

        $created = [];
        foreach ($products as $key => [$storeSlug, $category, $title, $code, $collection, $condition, $originalPrice, $singlePiece, $status]) {
            $store = Store::find($storeBySlug[$storeSlug]);
            $created[$key] = Product::updateOrCreate(
                ['code' => $code],
                [
                    'store_id' => $store->id,
                    'category' => $category,
                    'title' => $title,
                    'collection' => $collection,
                    'condition' => $condition,
                    'region_id' => $store->region_id,
                    'status' => $status,
                    'is_single_piece' => $singlePiece,
                    'original_price' => $originalPrice,
                    'return_policy_ack' => true,
                ],
            );
        }

        // Sale modes: product key => [ [type, price, deposit], ... ]
        $modes = [
            'dress1' => [['ready', 1250, null], ['custom', 1400, null], ['rent', 180, 300]],
            'abaya1' => [['ready', 950, null], ['custom', 1100, null], ['rent', 140, 250]],
            'rent1' => [['rent', 320, 500]],
            'noura3' => [['ready', 540, null]],
            'noura4' => [['custom', 750, null]],
            'care1' => [['ready', 85, null]],
            'acc1' => [['ready', 220, null]],
            'used1' => [['ready', 1800, null], ['rent', 250, 400]],
            'nobrand1' => [['ready', 390, null]],
            'used2' => [['ready', 310, null]],
        ];
        foreach ($modes as $key => $rows) {
            foreach ($rows as [$type, $price, $deposit]) {
                ProductSaleMode::updateOrCreate(
                    ['product_id' => $created[$key]->id, 'type' => $type],
                    ['price' => $price, 'deposit' => $deposit],
                );
            }
        }

        // Colors (qty hidden from buyers): product key => [ [name, qty], ... ]
        $colors = [
            'dress1' => [['وردي', 3], ['أسود', 1], ['ذهبي', 0]],
            'abaya1' => [['أسود', 3], ['كحلي', 1], ['بيج', 0]],
        ];
        foreach ($colors as $key => $rows) {
            foreach ($rows as [$name, $qty]) {
                ProductColor::updateOrCreate(
                    ['product_id' => $created[$key]->id, 'name' => $name],
                    ['qty' => $qty],
                );
            }
        }

        // Priced addons: product key => [ [name, price], ... ]
        $addons = [
            'dress1' => [['تطريز إضافي على الأكمام', 80], ['بطانة كاملة', 120], ['شيلة بنفس القماش', 200], ['ذيل للفستان', 150]],
            'abaya1' => [['تطريز ياقة إضافي', 60], ['أزرار ذهبية', 40]],
            'noura4' => [['تطريز كامل للأكمام', 150], ['بطانة حرير', 180]],
            'acc1' => [['نقش اسم على القطعة', 35], ['علبة مخمل', 25]],
        ];
        foreach ($addons as $key => $rows) {
            foreach ($rows as [$name, $price]) {
                ProductAddon::updateOrCreate(
                    ['product_id' => $created[$key]->id, 'name' => $name],
                    ['price' => $price],
                );
            }
        }
    }
}
