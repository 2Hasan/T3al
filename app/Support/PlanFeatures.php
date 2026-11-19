<?php

namespace App\Support;

/**
 * Features the owner can switch on per plan. Add a key here when a new feature
 * ships, then add its label in lang/ar and lang/en, and it can be sold immediately.
 */
class PlanFeatures
{
    /**
     * Apple and Google Wallet are part of loyalty cards, so they are not listed here.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return [
            'loyalty_cards',
            'order_queue',
            'smart_offers',
            'analytics',
        ];
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::keys() as $key) {
            $options[$key] = __('app.feature_'.$key);
        }

        return $options;
    }
}
