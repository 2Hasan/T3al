<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The cashier scan screen is now part of the loyalty_cards feature. A plan that
 * had cashier_scan gets loyalty_cards, and the separate key is removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('plans')->select('id', 'features')->get() as $plan) {
            $features = json_decode((string) $plan->features, true) ?: [];

            if (! in_array('cashier_scan', $features, true)) {
                continue;
            }

            $merged = array_values(array_unique(array_merge(
                array_diff($features, ['cashier_scan']),
                ['loyalty_cards'],
            )));

            DB::table('plans')->where('id', $plan->id)->update(['features' => json_encode($merged)]);
        }
    }

    public function down(): void
    {
        //
    }
};
