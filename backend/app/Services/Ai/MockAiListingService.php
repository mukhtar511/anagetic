<?php

namespace App\Services\Ai;

/** Returns the prototype's verbatim auto-fill result (aiFill). SPEC §3.4. */
class MockAiListingService implements AiListingService
{
    public function analyze(string $imagePath): array
    {
        return [
            'title' => 'فستان سهرة ساتان بأكمام طويلة — عنابي',
            'description' => 'فستان سهرة بقماش ساتان فاخر لامع، لون عنابي غامق، قصّة ضيقة من الأعلى وتنورة انسيابية، أكمام طويلة، سحاب خلفي مخفي، بطانة كاملة. مناسب للسهرات والزواجات. المقاسات المتوفرة: S–XL.',
            'color' => 'عنابي',
            'color_hex' => '#5e1224',
            'category' => 'dress',
            'suggested_price_min' => 850.0,
            'suggested_price_max' => 1150.0,
            'summary' => '✨ حللت الصورة: فستان سهرة · القماش: ساتان · اللون: عنابي · أكمام طويلة · بطانة كاملة — عبّيت الاسم والوصف واللون، والسعر المقترح بالسوق لمثله: ٨٥٠–١٬١٥٠ ر.س. راجعي وعدّلي اللي تبين ✏️',
        ];
    }
}
