<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Collected = 'collected';

    public function label(): string
    {
        return __('app.order_status_'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Preparing => 'warning',
            self::Ready => 'success',
            self::Collected => 'gray',
        };
    }
}
