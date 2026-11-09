<?php

namespace App\Exceptions;

use RuntimeException;

class NoRewardAvailable extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(__('app.no_reward_available'));
    }
}
