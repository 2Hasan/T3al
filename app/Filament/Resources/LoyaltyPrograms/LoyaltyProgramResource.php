<?php

namespace App\Filament\Resources\LoyaltyPrograms;

use App\Filament\Concerns\RestrictedToOwners;
use App\Filament\Resources\LoyaltyPrograms\Pages\ManageLoyaltyPrograms;
use App\Models\LoyaltyProgram;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LoyaltyProgramResource extends Resource
{
    use RestrictedToOwners;

    protected static ?string $model = LoyaltyProgram::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?string $modelLabel = 'بطاقة ولاء';

    protected static ?string $pluralModelLabel = 'بطاقات الولاء';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('البطاقة')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم البطاقة')
                            ->placeholder('بطاقة القهوة')
                            ->required()
                            ->maxLength(60),
                        TextInput::make('reward_label')
                            ->label('المكافأة')
                            ->placeholder('قهوة مجانية')
                            ->required()
                            ->maxLength(40),
                        TextInput::make('stamps_required')
                            ->label('عدد الأختام للمكافأة')
                            ->numeric()
                            ->integer()
                            ->minValue(2)
                            ->maxValue(30)
                            ->default(10)
                            ->required()
                            ->disabledOn('edit')
                            ->helperText('ما يتغيّر بعد إنشاء البطاقة، عشان ما تتأثر أختام العملاء الحاليين.'),
                        Toggle::make('is_active')
                            ->label('مفعّلة')
                            ->default(true)
                            ->inline(false),
                    ]),
                Section::make('الألوان')
                    ->columns(3)
                    ->schema([
                        ColorPicker::make('background_color')->label('الخلفية')->default('#3B2416')->required()->extraInputAttributes(['dir' => 'ltr']),
                        ColorPicker::make('foreground_color')->label('النص')->default('#FFFFFF')->required()->extraInputAttributes(['dir' => 'ltr']),
                        ColorPicker::make('label_color')->label('العناوين')->default('#E7D7C9')->required()->extraInputAttributes(['dir' => 'ltr']),
                    ]),
                Textarea::make('terms')
                    ->label('الشروط (تظهر خلف البطاقة)')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('background_color')->label(''),
                TextColumn::make('name')->label('البطاقة')->searchable(),
                TextColumn::make('reward_label')->label('المكافأة'),
                TextColumn::make('stamps_required')->label('الأختام'),
                TextColumn::make('loyalty_cards_count')->label('العملاء')->counts('loyaltyCards'),
                IconColumn::make('is_active')->label('مفعّلة')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageLoyaltyPrograms::route('/'),
        ];
    }
}
