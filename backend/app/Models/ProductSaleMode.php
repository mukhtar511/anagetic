<?php

namespace App\Models;

use App\Enums\ProductMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSaleMode extends Model
{
    use HasFactory;

    protected $table = 'product_modes';

    protected $fillable = [
        'product_id',
        'type',
        'price',
        'deposit',
        'prep_time',
        'exec_time',
        'rent_scope',
    ];

    protected $casts = [
        'type' => ProductMode::class,
        'price' => 'decimal:2',
        'deposit' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
