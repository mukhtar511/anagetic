<?php

namespace App\Filament\Resources;

use App\Enums\StoreStatus;
use App\Filament\Resources\StoreResource\Pages;
use App\Models\Store;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'المتاجر والبائعات';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'توثيق البائعات والمتاجر';

    protected static ?string $modelLabel = 'متجر';

    protected static ?string $pluralModelLabel = 'المتاجر';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('المتجر')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('البائعة')->searchable(),
                Tables\Columns\TextColumn::make('region.name')->label('المنطقة')->sortable(),
                Tables\Columns\TextColumn::make('verification_status')
                    ->label('التوثيق')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'verified' => 'موثّق',
                        'rejected' => 'مرفوض',
                        default => 'قيد المراجعة',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'verified' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (StoreStatus $state): string => match ($state) {
                        StoreStatus::Open => 'مفتوح',
                        StoreStatus::Paused => 'إيقاف الطلبات',
                        StoreStatus::Closed => 'مقفل',
                    })
                    ->color(fn (StoreStatus $state): string => match ($state) {
                        StoreStatus::Open => 'success',
                        StoreStatus::Paused => 'warning',
                        StoreStatus::Closed => 'danger',
                    }),
                Tables\Columns\TextColumn::make('rating_avg')->label('التقييم')->sortable(),
                Tables\Columns\TextColumn::make('completed_orders')->label('طلبات مكتملة')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('verification_status')
                    ->label('التوثيق')
                    ->options([
                        'pending' => 'قيد المراجعة',
                        'verified' => 'موثّق',
                        'rejected' => 'مرفوض',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'open' => 'مفتوح',
                        'paused' => 'إيقاف الطلبات',
                        'closed' => 'مقفل',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
                Tables\Actions\Action::make('verify')
                    ->label('توثيق')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Store $record): bool => $record->verification_status !== 'verified')
                    ->action(function (Store $record): void {
                        $record->update(['verification_status' => 'verified']);
                        $record->user?->update(['is_verified_seller' => true]);
                        Notification::make()->title('تم توثيق المتجر')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('رفض التوثيق')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Store $record): bool => $record->verification_status !== 'rejected')
                    ->action(function (Store $record): void {
                        $record->update(['verification_status' => 'rejected']);
                        $record->user?->update(['is_verified_seller' => false]);
                        Notification::make()->title('تم رفض التوثيق')->warning()->send();
                    }),
                Tables\Actions\Action::make('changeStatus')
                    ->label('حالة المتجر')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'open' => 'مفتوح',
                                'paused' => 'إيقاف الطلبات',
                                'closed' => 'مقفل مؤقتًا',
                            ])
                            ->required()
                            ->default(fn (Store $record): string => $record->status->value),
                    ])
                    ->action(function (Store $record, array $data): void {
                        $record->update(['status' => $data['status']]);
                        Notification::make()->title('تم تحديث حالة المتجر')->success()->send();
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('بيانات المتجر')->schema([
                Infolists\Components\TextEntry::make('name')->label('اسم المتجر'),
                Infolists\Components\TextEntry::make('user.name')->label('البائعة'),
                Infolists\Components\TextEntry::make('region.name')->label('المنطقة'),
                Infolists\Components\TextEntry::make('verification_status')->label('حالة التوثيق')->badge(),
                Infolists\Components\TextEntry::make('status')->label('حالة المتجر')->badge(),
                Infolists\Components\TextEntry::make('rating_avg')->label('التقييم'),
                Infolists\Components\TextEntry::make('completed_orders')->label('طلبات مكتملة'),
            ])->columns(2),
            Infolists\Components\Section::make('مستندات التوثيق')->schema([
                Infolists\Components\KeyValueEntry::make('verification_docs')
                    ->label('المستندات (معروف / سجل تجاري)'),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStores::route('/'),
            'view' => Pages\ViewStore::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
