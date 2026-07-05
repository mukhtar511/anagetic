<?php

namespace App\Filament\Resources;

use App\Enums\CouponOwner;
use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'المنصة';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'كوبونات المنصة';

    protected static ?string $modelLabel = 'كوبون';

    protected static ?string $pluralModelLabel = 'كوبونات المنصة';

    /** Only platform-owned coupons are managed here. SPEC §4.1. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('owner', CouponOwner::Platform->value);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('owner')->default(CouponOwner::Platform->value),
            Forms\Components\TextInput::make('code')
                ->label('الكود')
                ->required()
                ->rule('regex:/^[A-Z0-9]{3,15}$/')
                ->helperText('حروف كبيرة وأرقام، ٣ إلى ١٥ خانة')
                ->unique(ignoreRecord: true),
            Forms\Components\Select::make('kind')
                ->label('النوع')
                ->options([
                    'pct' => 'نسبة ٪',
                    'fix' => 'مبلغ ثابت',
                ])
                ->required()
                ->default('pct'),
            Forms\Components\TextInput::make('value')
                ->label('القيمة')
                ->numeric()
                ->required()
                ->minValue(0),
            Forms\Components\TextInput::make('cap')
                ->label('الحد الأقصى للخصم')
                ->numeric()
                ->minValue(0)
                ->helperText('اختياري — أقصى قيمة خصم للكوبون النسبي'),
            Forms\Components\TextInput::make('min')
                ->label('الحد الأدنى للطلب')
                ->numeric()
                ->minValue(0)
                ->default(0),
            Forms\Components\Toggle::make('active')->label('مفعّل')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('الكود')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('kind')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state->value ?? $state) === 'pct' ? 'نسبة ٪' : 'مبلغ ثابت'),
                Tables\Columns\TextColumn::make('value')->label('القيمة')->sortable(),
                Tables\Columns\TextColumn::make('cap')->label('السقف')->placeholder('—'),
                Tables\Columns\TextColumn::make('min')->label('الحد الأدنى'),
                Tables\Columns\TextColumn::make('used_count')->label('مرات الاستخدام')->sortable(),
                Tables\Columns\IconColumn::make('active')->label('مفعّل')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')->label('مفعّل'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
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
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
