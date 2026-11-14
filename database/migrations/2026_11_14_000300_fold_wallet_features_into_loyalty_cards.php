<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Apple and Google Wallet are now part of the loyalty_cards feature. Remove the
 * old separate entries from existing plans; loyalty_cards is kept as it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('plans')->select('id', 'features')->get() as $plan) {
            $features = json_decode((string) $plan->features, true) ?: [];
            $kept = array_values(array_diff($features, ['apple_wallet', 'google_wallet']));

            if ($kept !== $features) {
                DB::table('plans')->where('id', $plan->id)->update(['features' => json_encode($kept)]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
