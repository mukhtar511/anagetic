<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportedMessageResource\Pages;
use App\Models\Message;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportedMessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'المنصة';

    protected static ?int $navigationSort = 33;

    protected static ?string $navigationLabel = 'بلاغات الشات';

    protected static ?string $modelLabel = 'رسالة مُبلّغ عنها';

    protected static ?string $pluralModelLabel = 'بلاغات الشات';

    /** Only flagged messages (external-dealing filter). SPEC §4.10 / §7. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('flagged', true);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('sender.name')->label('المُرسِلة')->searchable(),
                Tables\Columns\TextColumn::make('conversation_id')->label('المحادثة')->sortable(),
                Tables\Columns\TextColumn::make('conversation.order.code')->label('الطلب')->placeholder('—'),
                Tables\Columns\TextColumn::make('body')->label('النص')->wrap()->limit(120)->searchable(),
                Tables\Columns\TextColumn::make('created_at')->label('التاريخ')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()->label('حذف الرسالة'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportedMessages::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
