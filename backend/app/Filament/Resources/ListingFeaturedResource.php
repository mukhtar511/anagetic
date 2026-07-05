<?php

namespace App\Filament\Resources;

use App\Enums\FeaturedScope;
use App\Enums\FeaturedStatus;
use App\Filament\Resources\ListingFeaturedResource\Pages;
use App\Models\ListingFeatured;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ListingFeaturedResource extends Resource
{
    protected static ?string $model = ListingFeatured::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'المنصة';

    protected static ?int $navigationSort = 31;

    protected static ?string $navigationLabel = 'إدارة المميز';

    protected static ?string $modelLabel = 'إعلان مميز';

    protected static ?string $pluralModelLabel = 'الإعلانات المميزة';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.title')->label('المنتج')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('scope')
                    ->label('النطاق')
                    ->badge()
                    ->formatStateUsing(fn (FeaturedScope $state): string => $state === FeaturedScope::All ? 'كل المناطق' : 'منطقتي فقط'),
                Tables\Columns\TextColumn::make('days')->label('المدة (يوم)')->sortable(),
                Tables\Columns\TextColumn::make('price')->label('السعر')->money('SAR')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (FeaturedStatus $state): string => $state === FeaturedStatus::Active ? 'نشط' : 'منتهٍ')
                    ->color(fn (FeaturedStatus $state): string => $state === FeaturedStatus::Active ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('expires_at')->label('ينتهي في')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'نشط',
                        'expired' => 'منتهٍ',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('expire')
                    ->label('إنهاء مبكر')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ListingFeatured $record): bool => $record->status === FeaturedStatus::Active)
                    ->action(function (ListingFeatured $record): void {
                        $record->update(['status' => FeaturedStatus::Expired]);
                        Notification::make()->title('تم إنهاء الإعلان المميز')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListingFeatureds::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
