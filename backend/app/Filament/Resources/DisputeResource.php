<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DisputeResource\Pages;
use App\Models\Dispute;
use App\Services\RentalService;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DisputeResource extends Resource
{
    protected static ?string $model = Dispute::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'النزاعات والاعتراضات';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'النزاعات';

    protected static ?string $modelLabel = 'نزاع';

    protected static ?string $pluralModelLabel = 'النزاعات';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('type')->label('النوع')->badge(),
                Tables\Columns\TextColumn::make('order.code')->label('الطلب')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'resolved' => 'محسوم',
                        default => 'مفتوح',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'resolved' => 'success',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('cut_value')->label('قيمة الخصم')->money('SAR'),
                Tables\Columns\TextColumn::make('created_at')->label('التاريخ')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'open' => 'مفتوح',
                        'resolved' => 'محسوم',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
                self::decideAction(),
            ]);
    }

    /** "قرار الخصم" — resolves the linked deposit through RentalService (auto-executes on wallets). SPEC §7. */
    public static function decideAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('decide')
            ->label('قرار الخصم')
            ->icon('heroicon-o-banknotes')
            ->color('danger')
            ->visible(fn (Dispute $record): bool => $record->status !== 'resolved' && $record->deposit_id !== null)
            ->form([
                Forms\Components\Placeholder::make('deposit_amount')
                    ->label('قيمة التأمين المحجوز')
                    ->content(fn (Dispute $record): string => number_format((float) ($record->deposit?->amount ?? 0), 2).' ر.س'),
                Forms\Components\Radio::make('cut_type')
                    ->label('نوع الخصم')
                    ->options([
                        'pct' => 'نسبة ٪',
                        'fix' => 'مبلغ ثابت',
                    ])
                    ->default('pct')
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('value')
                    ->label('القيمة')
                    ->helperText(fn (Forms\Get $get): string => $get('cut_type') === 'pct'
                        ? 'نسبة مئوية من التأمين (٠ إلى ١٠٠)'
                        : 'مبلغ بالريال يُخصم لصالح البائعة')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                Forms\Components\Textarea::make('reason')
                    ->label('سبب القرار')
                    ->required(),
            ])
            ->action(function (Dispute $record, array $data): void {
                $deposit = $record->deposit;

                if (! $deposit) {
                    Notification::make()->title('لا يوجد تأمين مرتبط بهذا النزاع')->danger()->send();

                    return;
                }

                $amount = (float) $deposit->amount;
                $cut = $data['cut_type'] === 'pct'
                    ? round($amount * ((float) $data['value'] / 100), 2)
                    : round((float) $data['value'], 2);
                $cut = min($cut, $amount);

                // Auto-executes on wallets: seller gets the cut, buyer the remainder. SPEC §7.
                app(RentalService::class)->resolveDamage($deposit, $cut, $data['reason']);

                $record->update([
                    'decision' => $data['cut_type'] === 'pct'
                        ? "خصم {$data['value']}٪ ({$cut} ر.س)"
                        : "خصم {$cut} ر.س",
                    'cut_value' => $cut,
                    'status' => 'resolved',
                    'decided_by' => auth()->id(),
                ]);

                Notification::make()
                    ->title('تم حسم النزاع وتنفيذ الخصم على المحافظ')
                    ->body("خُصم {$cut} ر.س من التأمين")
                    ->success()
                    ->send();
            });
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('بيانات النزاع')->schema([
                Infolists\Components\TextEntry::make('type')->label('النوع')->badge(),
                Infolists\Components\TextEntry::make('order.code')->label('كود الطلب'),
                Infolists\Components\TextEntry::make('status')->label('الحالة')->badge(),
                Infolists\Components\TextEntry::make('deposit.amount')->label('التأمين المحجوز')->money('SAR'),
                Infolists\Components\TextEntry::make('cut_value')->label('قيمة الخصم')->money('SAR'),
                Infolists\Components\TextEntry::make('decision')->label('القرار')->placeholder('لم يُحسم بعد'),
                Infolists\Components\TextEntry::make('decidedBy.name')->label('حسمه')->placeholder('—'),
            ])->columns(2),
            Infolists\Components\Section::make('أدلة الطرفين (صور التسليم والإرجاع)')->schema([
                Infolists\Components\KeyValueEntry::make('evidence')
                    ->label('الأدلة')
                    ->getStateUsing(function (Dispute $record): array {
                        $flat = [];
                        foreach ((array) $record->evidence as $key => $value) {
                            $flat[$key] = is_array($value) ? implode(' · ', array_map('strval', $value)) : (string) $value;
                        }

                        return $flat;
                    }),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDisputes::route('/'),
            'view' => Pages\ViewDispute::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
