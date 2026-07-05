<?php

namespace App\Services;

use App\Enums\FeaturedScope;
use App\Enums\FeaturedStatus;
use App\Models\ListingFeatured;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

/**
 * Featured-ad packages — SPEC §4.4.
 * Prepaid at publish (wallet/card), auto-expire, "expires tomorrow" notice.
 */
class FeaturedService
{
    public function __construct(private readonly WalletService $wallet) {}

    /** Package price from the matrix, or throw on an invalid combo. */
    public function price(FeaturedScope $scope, int $days): float
    {
        $matrix = config('anaqatuk.featured_prices');
        $key = $scope->value;

        if (! isset($matrix[$key][$days])) {
            throw new InvalidArgumentException('باقة غير صحيحة');
        }

        return (float) $matrix[$key][$days];
    }

    /**
     * Buy a featured placement for a product, paying from the seller's wallet.
     *
     * @throws RuntimeException on insufficient wallet balance (when paying by wallet).
     */
    public function purchase(Product $product, FeaturedScope $scope, int $days, User $seller, string $payVia = 'wallet'): ListingFeatured
    {
        $price = $this->price($scope, $days);

        if ($payVia === 'wallet') {
            if ($this->wallet->balance($seller) < $price) {
                throw new RuntimeException('رصيد المحفظة لا يكفي لدفع الباقة');
            }
            $this->wallet->debit($seller, 'featured_fee', $price, "دفع باقة إعلان مميز — {$product->title}", $product->code);
        }

        return $product->featured()->create([
            'scope' => $scope,
            'days' => $days,
            'price' => $price,
            'starts_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays($days),
            'status' => FeaturedStatus::Active,
        ]);
    }

    /** Expire any active placements past their window. Runs daily. SPEC §6. */
    public function expireDue(): int
    {
        return ListingFeatured::where('status', FeaturedStatus::Active)
            ->where('expires_at', '<=', Carbon::now())
            ->update(['status' => FeaturedStatus::Expired]);
    }

    /** Placements expiring within 24h that still need a notice. */
    public function dueForExpiryNotice(): Collection
    {
        return ListingFeatured::where('status', FeaturedStatus::Active)
            ->where('expiring_notified', false)
            ->whereBetween('expires_at', [Carbon::now(), Carbon::now()->addDay()])
            ->get();
    }
}
