<?php

namespace App\Enums;

enum MerchantStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Trial => __('app.status_trial'),
            self::Active => __('app.status_active'),
            self::Suspended => __('app.status_suspended'),
        };
    }

    public function isOperational(): bool
    {
        return $this !== self::Suspended;
    }
}
