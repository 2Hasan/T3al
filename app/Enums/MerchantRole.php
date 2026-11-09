<?php

namespace App\Enums;

enum MerchantRole: string
{
    case Owner = 'owner';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'صاحب المحل',
            self::Staff => 'كاشير',
        };
    }
}
