<?php

namespace App\Filament\Admin\Resources\Merchants;

use App\Actions\RecordPayment;
use App\Enums\MerchantStatus;
use App\Filament\Admin\Resources\Merchants\Pages\ListMerchants;
use App\Filament\Admin\Resources\Merchants\Pages\ViewMerchant;
use App\Filament\Admin\Resources\Merchants\RelationManagers\PaymentsRelationManager;
use App\Models\Merchant;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Every shop on the platform: plan, billing period and the actions staff need.
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

    public static function getNavigationBadge(): ?string
    {
        $expiring = Merchant::query()->endingWithin(7)->count();

        return $expiring > 0 ? (string) $expiring : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('app.badge_ending_soon');
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
                ->options(self::statusOptions())
                ->required(),
            DateTimePicker::make('trial_ends_at')->label(__('app.trial_ends_at')),
            DateTimePicker::make('paid_until')->label(__('app.paid_until')),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('app.section_shop'))
                ->columns(2)
                ->schema([
                    TextEntry::make('name')->label(__('app.shop_name')),
                    TextEntry::make('slug')->label(__('app.slug'))->copyable(),
                    TextEntry::make('owners')
                        ->label(__('app.owners'))
                        ->state(fn (Merchant $record): string => $record->users()->wherePivot('role', 'owner')->pluck('name')->join(', ') ?: '—'),
                    TextEntry::make('created_at')->label(__('app.joined'))->date(),
                ]),
            Section::make(__('app.section_billing'))
                ->columns(2)
                ->schema([
                    TextEntry::make('plan.name')->label(__('app.plan'))->placeholder(__('app.no_plan')),
                    TextEntry::make('plan.price_per_branch')
                        ->label(__('app.price_per_branch'))
                        ->formatStateUsing(fn (?string $state): string => $state ? number_format((float) $state, 3).' BHD' : '—'),
                    TextEntry::make('branches_count')
                        ->label(__('app.branches'))
                        ->state(fn (Merchant $record): int => $record->branches()->count()),
                    TextEntry::make('monthly_amount')
                        ->label(__('app.monthly_amount'))
                        ->state(fn (Merchant $record): string => $record->monthlyAmount().' BHD'),
                    TextEntry::make('status')
                        ->label(__('app.status'))
                        ->badge()
                        ->formatStateUsing(fn (MerchantStatus $state): string => $state->label()),
                    TextEntry::make('period_end')
                        ->label(__('app.period_ends'))
                        ->state(fn (Merchant $record): ?Carbon => $record->periodEndsAt())
                        ->date()
                        ->placeholder('—'),
                ]),
            Section::make(__('app.section_activity'))
                ->columns(3)
                ->schema([
                    TextEntry::make('customers_count')
                        ->label(__('app.customers'))
                        ->state(fn (Merchant $record): int => $record->customers()->count()),
                    TextEntry::make('stamps_30')
                        ->label(__('app.stamps_last_30'))
                        ->state(fn (Merchant $record): int => (int) $record->cardActivities()
                            ->where('created_at', '>=', now()->subDays(30))
                            ->sum('quantity')),
                    TextEntry::make('last_activity')
                        ->label(__('app.last_activity'))
                        ->state(fn (Merchant $record): ?Carbon => $record->cardActivities()->latest('created_at')->value('created_at'))
                        ->since()
                        ->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('plan')->withCount('branches')->latest('id'))
            ->defaultSort('id', 'desc')
            ->recordUrl(fn (Merchant $record): string => self::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.shop_name'))
                    ->searchable()
                    ->description(fn (Merchant $record): string => $record->slug),
                TextColumn::make('plan.name')
                    ->label(__('app.plan'))
                    ->badge()
                    ->color('gray')
                    ->placeholder(__('app.no_plan')),
                TextColumn::make('branches_count')->label(__('app.branches'))->alignCenter(),
                TextColumn::make('monthly_amount')
                    ->label(__('app.monthly_amount'))
                    ->state(fn (Merchant $record): string => $record->monthlyAmount($record->branches_count).' BHD'),
                TextColumn::make('status')
                    ->label(__('app.status'))
                    ->badge()
                    ->formatStateUsing(fn (MerchantStatus $state): string => $state->label())
                    ->color(fn (MerchantStatus $state): string => match ($state) {
                        MerchantStatus::Active => 'success',
                        MerchantStatus::Trial => 'warning',
                        MerchantStatus::Suspended => 'danger',
                    }),
                TextColumn::make('period_end')
                    ->label(__('app.period_ends'))
                    ->state(fn (Merchant $record): ?Carbon => $record->periodEndsAt())
                    ->date()
                    ->placeholder('—')
                    ->description(fn (Merchant $record): ?string => self::daysLabel($record)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('app.status'))
                    ->options(self::statusOptions()),
                SelectFilter::make('plan')
                    ->label(__('app.plan'))
                    ->relationship('plan', 'name'),
                Filter::make('ending_soon')
                    ->label(__('app.ending_soon'))
                    ->query(fn (Builder $query) => $query->endingWithin(7)),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    self::recordPaymentAction(),
                    self::extendTrialAction(),
                    self::suspendAction(),
                    self::activateAction(),
                    EditAction::make(),
                ])->icon(Heroicon::OutlinedEllipsisVertical),
            ])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMerchants::route('/'),
            'view' => ViewMerchant::route('/{record}'),
        ];
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return collect(MerchantStatus::cases())
            ->mapWithKeys(fn (MerchantStatus $status) => [$status->value => $status->label()])
            ->all();
    }

    public static function daysLabel(Merchant $record): ?string
    {
        $days = $record->daysLeft();

        if ($days === null) {
            return null;
        }

        return $days >= 0
            ? __('app.days_left', ['count' => $days])
            : __('app.expired_days_ago', ['count' => abs($days)]);
    }

    public static function recordPaymentAction(): Action
    {
        return Action::make('recordPayment')
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

                Notification::make()
                    ->title(__('app.payment_recorded', ['shop' => $record->name]))
                    ->body(__('app.paid_until_now', ['date' => $record->refresh()->paid_until?->format('Y-m-d')]))
                    ->success()
                    ->send();
            });
    }

    public static function extendTrialAction(): Action
    {
        return Action::make('extendTrial')
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

                Notification::make()->title(__('app.trial_extended', ['shop' => $record->name]))->success()->send();
            });
    }

    public static function suspendAction(): Action
    {
        return Action::make('suspend')
            ->label(__('app.suspend'))
            ->icon(Heroicon::OutlinedPauseCircle)
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (Merchant $record): bool => $record->status !== MerchantStatus::Suspended)
            ->action(function (Merchant $record): void {
                $record->update(['status' => MerchantStatus::Suspended]);
                Notification::make()->title(__('app.shop_suspended', ['shop' => $record->name]))->warning()->send();
            });
    }

    public static function activateAction(): Action
    {
        return Action::make('activate')
            ->label(__('app.activate'))
            ->icon(Heroicon::OutlinedPlayCircle)
            ->color('success')
            ->visible(fn (Merchant $record): bool => $record->status === MerchantStatus::Suspended)
            ->action(function (Merchant $record): void {
                $record->update(['status' => MerchantStatus::Active]);
                Notification::make()->title(__('app.shop_activated', ['shop' => $record->name]))->success()->send();
            });
    }
}
