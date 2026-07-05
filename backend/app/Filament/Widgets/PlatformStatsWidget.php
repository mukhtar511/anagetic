<?php

namespace App\Filament\Widgets;

use App\Models\Deposit;
use App\Models\EscrowTransaction;
use App\Models\ListingFeatured;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -3;

    protected function getStats(): array
    {
        $gmv = (float) Order::whereIn('status', ['completed', 'delivered', 'paid_escrow'])->sum('total');
        $commissions = (float) EscrowTransaction::where('status', 'released')->sum('commission');
        $featuredRevenue = (float) ListingFeatured::sum('price');
        $heldDeposits = (float) Deposit::where('status', 'held')->sum('amount');

        return [
            Stat::make('GMV — إجمالي المبيعات', number_format($gmv, 2).' ر.س')
                ->description('الطلبات المدفوعة والمكتملة')
                ->color('primary'),
            Stat::make('إجمالي العمولات', number_format($commissions, 2).' ر.س')
                ->description('عمولة ١٢٪ على المحرَّر')
                ->color('success'),
            Stat::make('إيراد المميز', number_format($featuredRevenue, 2).' ر.س')
                ->description('باقات الإعلانات المميزة')
                ->color('warning'),
            Stat::make('التأمينات المحجوزة', number_format($heldDeposits, 2).' ر.س')
                ->description('تأمينات التأجير القائمة')
                ->color('info'),
        ];
    }
}
