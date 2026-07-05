<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppealTicketResource\Pages;
use App\Models\AppealTicket;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AppealTicketResource extends Resource
{
    protected static ?string $model = AppealTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';

    protected static ?string $navigationGroup = 'النزاعات والاعتراضات';

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationLabel = 'الاعتراضات';

    protected static ?string $modelLabel = 'اعتراض';

    protected static ?string $pluralModelLabel = 'الاعتراضات';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ref')->label('المرجع')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('deposit.order.code')->label('الطلب')->searchable(),
                Tables\Columns\TextColumn::make('deposit.amount')->label('التأمين')->money('SAR'),
                Tables\Columns\TextColumn::make('user.name')->label('المستخدمة')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'reviewing' => 'قيد المراجعة',
                        'resolved' => 'محسوم',
                        default => 'مفتوح',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'reviewing' => 'info',
                        'resolved' => 'success',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('reviewedBy.name')->label('المراجِع')->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('التاريخ')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'open' => 'مفتوح',
                        'reviewing' => 'قيد المراجعة',
                        'resolved' => 'محسوم',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('startReview')
                    ->label('بدء المراجعة')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn (AppealTicket $record): bool => $record->status === 'open')
                    ->requiresConfirmation()
                    ->action(function (AppealTicket $record): void {
                        $record->update([
                            'status' => 'reviewing',
                            'reviewed_by' => auth()->id(),
                        ]);
                        Notification::make()->title('بدأت مراجعة الاعتراض')->success()->send();
                    }),
                Tables\Actions\Action::make('resolve')
                    ->label('حسم الاعتراض')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (AppealTicket $record): bool => $record->status !== 'resolved')
                    ->form([
                        Forms\Components\Textarea::make('resolution')
                            ->label('قرار الحسم')
                            ->required(),
                    ])
                    ->action(function (AppealTicket $record, array $data): void {
                        $record->update([
                            'status' => 'resolved',
                            'reviewed_by' => $record->reviewed_by ?? auth()->id(),
                            'resolution' => $data['resolution'],
                        ]);
                        Notification::make()->title('تم حسم الاعتراض')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppealTickets::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
