<?php

namespace App\Http\Controllers\Seller;

use App\Enums\DepositStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Api\ApiController;
use App\Models\Deposit;
use App\Models\EscrowTransaction;
use App\Models\Order;
use App\Services\EscrowService;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    use ResolvesSellerStore;

    /** GET /api/seller/dashboard */
    public function index(Request $request)
    {
        $store = $this->sellerStore($request);
        $monthStart = now()->startOfMonth();

        $completedThisMonth = Order::where('store_id', $store->id)
            ->where('status', OrderStatus::Completed->value)
            ->where('completed_at', '>=', $monthStart);

        $sales = (float) (clone $completedThisMonth)->sum('subtotal');

        $net = (float) EscrowTransaction::where('store_id', $store->id)
            ->where('status', 'released')
            ->where('released_at', '>=', $monthStart)
            ->sum('net_amount');

        $heldEscrow = (float) EscrowTransaction::where('store_id', $store->id)
            ->where('status', 'held')
            ->sum('held_amount');

        return $this->ok([
            'month_sales' => $sales,
            'month_net' => $net,
            'commission_rate' => app(EscrowService::class)->commissionRate(),
            'orders_count' => Order::where('store_id', $store->id)->count(),
            'completed_orders' => (clone $completedThisMonth)->count(),
            'held_escrow' => $heldEscrow,
            'active_deposits' => Deposit::where('store_id', $store->id)
                ->where('status', DepositStatus::Held->value)
                ->count(),
            'rating_avg' => (float) $store->rating_avg,
        ]);
    }
}
