<?php

namespace App\Actions;

use App\Models\Customer;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyProgram;
use App\Support\BahrainPhone;
use Illuminate\Support\Facades\DB;

/**
 * Signs a customer up to a merchant's loyalty program. Signing up again with the
 * same phone number returns the existing card instead of creating a duplicate.
 */
class IssueLoyaltyCard
{
    public function __construct(private SyncWalletPasses $syncWalletPasses) {}

    public function handle(LoyaltyProgram $program, string $name, string $phone): LoyaltyCard
    {
        $card = DB::transaction(function () use ($program, $name, $phone): LoyaltyCard {
            $customer = Customer::query()->firstOrCreate(
                ['merchant_id' => $program->merchant_id, 'phone' => BahrainPhone::normalize($phone)],
                ['name' => $name],
            );

            return LoyaltyCard::query()->firstOrCreate(
                ['loyalty_program_id' => $program->id, 'customer_id' => $customer->id],
                ['merchant_id' => $program->merchant_id, 'code' => LoyaltyCard::generateCode()],
            );
        });

        if ($card->wasRecentlyCreated) {
            $card->refresh();
            $this->syncWalletPasses->handle($card);
        }

        return $card;
    }
}
