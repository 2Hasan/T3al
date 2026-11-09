<?php

namespace App\Exceptions;

use RuntimeException;

class NoRewardAvailable extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('ما فيه مكافأة متاحة في هذي البطاقة.');
    }
}
