<?php

namespace App\Actions;

use App\Enums\MerchantStatus;
use App\Models\Merchant;
use App\Models\Payment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Records a bank transfer and extends the shop's paid period. Renewals stack on top of
 * the current end date, so paying early never loses days.
 */
class RecordPayment
{
    public function handle(
        Merchant $merchant,
        int $months,
        string $amount,
        CarbonInterface $paidOn,
        ?string $reference,
        ?string $note,
        ?User $recordedBy,
    ): Payment {
        return DB::transaction(function () use ($merchant, $months, $amount, $paidOn, $reference, $note, $recordedBy): Payment {
            $payment = Payment::query()->create([
                'merchant_id' => $merchant->id,
                'plan_id' => $merchant->plan_id,
                'amount' => $amount,
                'months' => $months,
                'reference' => $reference,
                'paid_on' => $paidOn,
                'note' => $note,
                'recorded_by' => $recordedBy?->id,
            ]);

            $start = $merchant->paid_until && $merchant->paid_until->isFuture()
                ? $merchant->paid_until
                : now();

            $merchant->update([
                'paid_until' => $start->copy()->addMonthsNoOverflow($months),
                'status' => MerchantStatus::Active,
            ]);

            return $payment;
        });
    }
}
