<?php

namespace App\Support;

use App\Enums\CardActivityType;
use App\Models\CardActivity;
use App\Models\LoyaltyCard;
use App\Models\Merchant;
use Carbon\CarbonImmutable;

/**
 * Numbers for the shop's analytics page, all in the shop's own timezone. Everything
 * is read from card activity, so the figures match what the till recorded.
 */
class ShopAnalytics
{
    public const WINDOW_DAYS = 30;

    /**
     * @return array{
     *     stamps: int, visits: int, new_customers: int, redemptions: int,
     *     active_customers: int, returning_rate: int,
     *     daily: array<int, array{label: string, count: int}>,
     *     weekdays: array<int, array{label: string, count: int}>
     * }
     */
    public static function for(Merchant $shop): array
    {
        $now = CarbonImmutable::now($shop->timezone);
        $since = $now->subDays(self::WINDOW_DAYS - 1)->startOfDay();

        $stamps = CardActivity::query()
            ->where('merchant_id', $shop->id)
            ->where('type', CardActivityType::Stamp)
            ->where('created_at', '>=', $since->utc())
            ->get(['loyalty_card_id', 'quantity', 'created_at']);

        $redemptions = CardActivity::query()
            ->where('merchant_id', $shop->id)
            ->where('type', CardActivityType::Redeem)
            ->where('created_at', '>=', $since->utc())
            ->count();

        $newCustomers = LoyaltyCard::query()
            ->where('merchant_id', $shop->id)
            ->where('created_at', '>=', $since->utc())
            ->count();

        // Returning customers: cards with at least two visits (scans), out of all cards that visited.
        $visitsPerCard = CardActivity::query()
            ->where('merchant_id', $shop->id)
            ->where('type', CardActivityType::Stamp)
            ->selectRaw('loyalty_card_id, count(*) as visits')
            ->groupBy('loyalty_card_id')
            ->pluck('visits');

        $everVisited = $visitsPerCard->count();
        $returning = $visitsPerCard->filter(fn (int $visits): bool => $visits >= 2)->count();

        $daily = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = $now->subDays($i);
            $daily[$day->format('Y-m-d')] = ['label' => $day->format('j/n'), 'count' => 0];
        }

        $weekdays = [];
        $sunday = $now->startOfWeek(CarbonImmutable::SUNDAY);
        for ($d = 0; $d < 7; $d++) {
            $weekdays[$d] = ['label' => $sunday->addDays($d)->locale(app()->getLocale())->isoFormat('dddd'), 'count' => 0];
        }

        foreach ($stamps as $activity) {
            $local = $activity->created_at->setTimezone($shop->timezone);
            $key = $local->format('Y-m-d');

            if (isset($daily[$key])) {
                $daily[$key]['count'] += $activity->quantity;
            }

            $weekdays[$local->dayOfWeek]['count'] += $activity->quantity;
        }

        return [
            'stamps' => (int) $stamps->sum('quantity'),
            'visits' => $stamps->count(),
            'new_customers' => $newCustomers,
            'redemptions' => $redemptions,
            'active_customers' => $stamps->pluck('loyalty_card_id')->unique()->count(),
            'returning_rate' => $everVisited > 0 ? (int) round($returning / $everVisited * 100) : 0,
            'daily' => array_values($daily),
            'weekdays' => array_values($weekdays),
        ];
    }
}
