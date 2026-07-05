<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'الطلبات والمالية';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'الطلبات والمبالغ المحجوزة';

    protected static ?string $modelLabel = 'طلب';

    protected static ?string $pluralModelLabel = 'الطلبات';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('الكود')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('buyer.name')->label('المشترية')->searchable(),
                Tables\Columns\TextColumn::make('store.name')->label('المتجر')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
                    ->color(fn (OrderStatus $state): string => match ($state) {
                        OrderStatus::Completed, OrderStatus::Delivered => 'success',
                        OrderStatus::Cancelled => 'danger',
                        OrderStatus::ReturnRequested, OrderStatus::ReturnPickup, OrderStatus::ReturnedRefunded => 'warning',
                        OrderStatus::PendingPayment => 'gray',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('total')->label('الإجمالي')->money('SAR')->sortable(),
                Tables\Columns\TextColumn::make('deposit_total')->label('التأمين')->money('SAR'),
                Tables\Columns\TextColumn::make('escrow_held')
                    ->label('محجوز في الضمان')
                    ->money('SAR')
                    ->getStateUsing(fn (Order $record): float => (float) $record->escrowTransactions()
                        ->where('status', 'held')->sum('held_amount')),
                Tables\Columns\TextColumn::make('placed_at')->label('تاريخ الطلب')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options(collect(OrderStatus::cases())
                        ->mapWithKeys(fn (OrderStatus $s): array => [$s->value => $s->label()])
                        ->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('بيانات الطلب')->schema([
                Infolists\Components\TextEntry::make('code')->label('الكود'),
                Infolists\Components\TextEntry::make('buyer.name')->label('المشترية'),
                Infolists\Components\TextEntry::make('store.name')->label('المتجر'),
                Infolists\Components\TextEntry::make('status')->label('الحالة')
                    ->formatStateUsing(fn (OrderStatus $state): string => $state->label())->badge(),
                Infolists\Components\TextEntry::make('subtotal')->label('المجموع الفرعي')->money('SAR'),
                Infolists\Components\TextEntry::make('delivery_fee')->label('التوصيل')->money('SAR'),
                Infolists\Components\TextEntry::make('discount')->label('الخصم')->money('SAR'),
                Infolists\Components\TextEntry::make('deposit_total')->label('التأمين')->money('SAR'),
                Infolists\Components\TextEntry::make('total')->label('الإجمالي')->money('SAR'),
            ])->columns(3),
            Infolists\Components\Section::make('عناصر الطلب')->schema([
                Infolists\Components\RepeatableEntry::make('items')
                    ->label('العناصر')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')->label('المنتج'),
                        Infolists\Components\TextEntry::make('mode')->label('النمط'),
                        Infolists\Components\TextEntry::make('color')->label('اللون')->placeholder('—'),
                        Infolists\Components\TextEntry::make('qty')->label('الكمية'),
                        Infolists\Components\TextEntry::make('line_total')->label('إجمالي السطر')->money('SAR'),
                    ])
                    ->columns(5),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
