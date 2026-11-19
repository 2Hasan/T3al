<?php

namespace App\Support;

use App\Enums\OfferType;
use App\Models\LoyaltyCard;
use App\Models\Merchant;
use App\Models\Offer;
use Carbon\CarbonInterface;

/**
 * Works out how many bonus stamps the active offers add to a scan. It is called
 * inside the stamp transaction, before the card's last visit is updated.
 */
class StampOffers
{
    /** A single scan never gets more bonus than this, whatever the offers say. */
    public const MAX_BONUS_PER_SCAN = 10;

    /**
     * @return array{bonus: int, birthday: bool}
     */
    public static function bonusFor(LoyaltyCard $card, Merchant $shop, int $quantity, CarbonInterface $now): array
    {
        if (! $shop->hasFeature('smart_offers')) {
            return ['bonus' => 0, 'birthday' => false];
        }

        $offers = Offer::query()->where('merchant_id', $shop->id)->where('is_active', true)->get();

        if ($offers->isEmpty()) {
            return ['bonus' => 0, 'birthday' => false];
        }

        $localNow = $now->copy()->setTimezone($shop->timezone);
        $bonus = 0;
        $birthday = false;

        // Multiplier: the highest multiplier that applies today adds the extra stamps.
        $multiplier = $offers
            ->where('type', OfferType::Multiplier)
            ->filter(fn (Offer $offer): bool => empty($offer->weekdays)
                || in_array($localNow->dayOfWeek, array_map('intval', $offer->weekdays), true))
            ->max('multiplier');

        if ($multiplier && $multiplier > 1) {
            $bonus += $quantity * ($multiplier - 1);
        }

        // Win-back: only when the card has a previous visit that is old enough.
        foreach ($offers->where('type', OfferType::WinBack) as $offer) {
            if ($card->last_visit_at !== null && $offer->inactive_days !== null
                && $card->last_visit_at->lte($now->copy()->subDays($offer->inactive_days))) {
                $bonus += $offer->bonus_stamps;
            }
        }

        // Birthday: once per calendar year, on the customer's birthday in the shop's timezone.
        $customerBirthday = $card->customer?->birthday;
        $alreadyThisYear = $card->birthday_bonus_at?->setTimezone($shop->timezone)->year === $localNow->year;

        if ($customerBirthday && ! $alreadyThisYear
            && $customerBirthday->month === $localNow->month && $customerBirthday->day === $localNow->day) {
            $offer = $offers->where('type', OfferType::Birthday)->sortByDesc('bonus_stamps')->first();

            if ($offer) {
                $bonus += $offer->bonus_stamps;
                $birthday = true;
            }
        }

        return ['bonus' => min($bonus, self::MAX_BONUS_PER_SCAN), 'birthday' => $birthday];
    }
}
