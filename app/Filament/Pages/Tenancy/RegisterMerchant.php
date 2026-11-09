<?php

namespace App\Filament\Pages\Tenancy;

use App\Enums\MerchantRole;
use App\Models\Merchant;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Shown right after sign-up: creates the shop, makes the user its owner and
 * gives it a ready-to-use stamp card so the first customer can join immediately.
 */
class RegisterMerchant extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'سجّل محلك';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم المحل')
                    ->required()
                    ->maxLength(60),
                TextInput::make('reward_label')
                    ->label('المكافأة بعد 10 أختام')
                    ->placeholder('قهوة مجانية')
                    ->required()
                    ->maxLength(40),
            ]);
    }

    /**
     * @param  array{name: string, reward_label: string}  $data
     */
    protected function handleRegistration(array $data): Merchant
    {
        return DB::transaction(function () use ($data): Merchant {
            $merchant = Merchant::create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'trial_ends_at' => now()->addDays(14),
            ]);

            $merchant->users()->attach(auth()->id(), ['role' => MerchantRole::Owner->value]);

            $merchant->loyaltyPrograms()->create([
                'name' => 'بطاقة '.$data['name'],
                'reward_label' => $data['reward_label'],
                'stamps_required' => 10,
            ]);

            return $merchant;
        });
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'shop';

        do {
            $slug = $base.'-'.Str::lower(Str::random(4));
        } while (Merchant::where('slug', $slug)->exists());

        return $slug;
    }
}
