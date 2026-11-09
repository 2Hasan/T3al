<?php

namespace App\Filament\Admin\Resources\Merchants;

use App\Actions\RecordPayment;
use App\Enums\MerchantStatus;
use App\Filament\Admin\Resources\Merchants\Pages\ManageMerchants;
use App\Models\Merchant;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Every shop on the platform: its plan, its billing period, and the actions staff
 * need (record a bank transfer, extend a trial, suspend or reactivate).
 */
class MerchantResource extends Resource
{
    protected static ?string $model = Merchant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('app.admin_shops');
    }

    public static function getModelLabel(): string
    {
        return __('app.shop');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.admin_shops');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('app.shop_name'))->required()->maxLength(60),
            Select::make('plan_id')
                ->label(__('app.plan'))
                ->relationship('plan', 'name')
                ->placeholder(__('app.no_plan')),
            Select::make('status')
                ->label(__('app.status'))
                ->options(collect(MerchantStatus::cases())->mapWithKeys(fn (MerchantStatus $status) => [$status->value => $status->label()]))
                ->required(),
            DateTimePicker::make('trial_ends_at')->label(__('app.trial_ends_at')),
            DateTimePicker::make('paid_until')->label(__('app.paid_until')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('plan')->withCount('branches')->latest('id'))
            ->columns([
                TextColumn::make('name')->label(__('app.shop_name'))->searchable(),
                TextColumn::make('plan.name')->label(__('app.plan'))->placeholder(__('app.no_plan')),
                TextColumn::make('branches_count')->label(__('app.branches')),
                TextColumn::make('status')
                    ->label(__('app.status'))
                    ->badge()
                    ->formatStateUsing(fn (MerchantStatus $state): string => $state->label())
                    ->color(fn (MerchantStatus $state): string => match ($state) {
                        MerchantStatus::Active => 'success',
                        MerchantStatus::Trial => 'warning',
                        MerchantStatus::Suspended => 'danger',
                    }),
                TextColumn::make('trial_ends_at')->label(__('app.trial_ends_at'))->date()->placeholder('—'),
                TextColumn::make('paid_until')->label(__('app.paid_until'))->date()->placeholder('—'),
            ])
            ->recordActions([
                EditAction::make(),

                Action::make('recordPayment')
                    ->label(__('app.record_payment'))
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->schema([
                        Select::make('months')
                            ->label(__('app.months'))
                            ->options([1 => '1', 3 => '3', 6 => '6', 12 => '12'])
                            ->default(1)
                            ->required(),
                        TextInput::make('amount')
                            ->label(__('app.amount'))
                            ->helperText(__('app.amount_help'))
                            ->numeric()
                            ->step('0.001')
                            ->suffix('BHD')
                            ->required(),
                        DateTimePicker::make('paid_on')
                            ->label(__('app.paid_on'))
                            ->default(now())
                            ->required(),
                        TextInput::make('reference')->label(__('app.reference'))->maxLength(80),
                        TextInput::make('note')->label(__('app.note'))->maxLength(255),
                    ])
                    ->fillForm(fn (Merchant $record): array => ['amount' => $record->monthlyAmount()])
                    ->action(function (Merchant $record, array $data): void {
                        app(RecordPayment::class)->handle(
                            merchant: $record,
                            months: (int) $data['months'],
                            amount: (string) $data['amount'],
                            paidOn: Carbon::parse($data['paid_on']),
                            reference: $data['reference'] ?? null,
                            note: $data['note'] ?? null,
                            recordedBy: auth()->user(),
                        );
                    }),

                Action::make('extendTrial')
                    ->label(__('app.extend_trial'))
                    ->icon(Heroicon::OutlinedClock)
                    ->schema([
                        TextInput::make('days')->label(__('app.days'))->numeric()->default(14)->minValue(1)->maxValue(90)->required(),
                    ])
                    ->action(function (Merchant $record, array $data): void {
                        $start = $record->trial_ends_at && $record->trial_ends_at->isFuture() ? $record->trial_ends_at : now();

                        $record->update([
                            'trial_ends_at' => $start->copy()->addDays((int) $data['days']),
                            'status' => $record->status === MerchantStatus::Suspended ? MerchantStatus::Trial : $record->status,
                        ]);
                    }),

                Action::make('suspend')
                    ->label(__('app.suspend'))
                    ->icon(Heroicon::OutlinedPauseCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Merchant $record): bool => $record->status !== MerchantStatus::Suspended)
                    ->action(fn (Merchant $record) => $record->update(['status' => MerchantStatus::Suspended])),

                Action::make('activate')
                    ->label(__('app.activate'))
                    ->icon(Heroicon::OutlinedPlayCircle)
                    ->color('success')
                    ->visible(fn (Merchant $record): bool => $record->status === MerchantStatus::Suspended)
                    ->action(fn (Merchant $record) => $record->update(['status' => MerchantStatus::Active])),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMerchants::route('/'),
        ];
    }
}
