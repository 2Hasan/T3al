<?php

namespace App\Enums;

enum CardActivityType: string
{
    case Stamp = 'stamp';
    case Redeem = 'redeem';

    public function label(): string
    {
        return match ($this) {
            self::Stamp => __('app.stamp'),
            self::Redeem => __('app.redeem_reward'),
        };
    }
}
