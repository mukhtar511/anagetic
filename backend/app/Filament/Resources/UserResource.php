<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'المنصة';

    protected static ?int $navigationSort = 32;

    protected static ?string $navigationLabel = 'المستخدمون';

    protected static ?string $modelLabel = 'مستخدمة';

    protected static ?string $pluralModelLabel = 'المستخدمون';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('الاسم')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->label('الجوال')->searchable(),
                Tables\Columns\TextColumn::make('region.name')->label('المنطقة')->sortable(),
                Tables\Columns\IconColumn::make('is_verified_seller')->label('بائعة موثّقة')->boolean(),
                Tables\Columns\IconColumn::make('is_admin')->label('أدمن')->boolean(),
                Tables\Columns\TextColumn::make('suspended_at')
                    ->label('حالة الحساب')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'موقوف' : 'نشط')
                    ->color(fn ($state): string => $state ? 'danger' : 'success'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('suspended_at')
                    ->label('موقوف')
                    ->nullable()
                    ->placeholder('الكل')
                    ->trueLabel('الموقوفون')
                    ->falseLabel('النشطون'),
                Tables\Filters\TernaryFilter::make('is_verified_seller')->label('بائعة موثّقة'),
            ])
            ->actions([
                Tables\Actions\Action::make('suspend')
                    ->label('إيقاف / حظر')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->suspended_at === null)
                    ->action(function (User $record): void {
                        $record->update(['suspended_at' => now()]);
                        Notification::make()->title('تم إيقاف الحساب')->success()->send();
                    }),
                Tables\Actions\Action::make('unsuspend')
                    ->label('رفع الإيقاف')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->suspended_at !== null)
                    ->action(function (User $record): void {
                        $record->update(['suspended_at' => null]);
                        Notification::make()->title('تم رفع الإيقاف')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
