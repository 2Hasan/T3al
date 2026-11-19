<?php

namespace App\Enums;

enum OfferType: string
{
    /** Every stamp counts as several stamps, on chosen weekdays (or every day). */
    case Multiplier = 'multiplier';

    /** A customer who has been away for a while gets bonus stamps on their next visit. */
    case WinBack = 'win_back';

    /** The customer gets bonus stamps on their birthday, once a year. */
    case Birthday = 'birthday';

    public function label(): string
    {
        return __('app.offer_type_'.$this->value);
    }
}
