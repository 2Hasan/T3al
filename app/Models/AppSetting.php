<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Platform-wide settings edited from the admin panel. Secrets such as the SMTP
 * password are stored encrypted by the 'encrypted:array' cast.
 */
#[Fillable(['key', 'value'])]
class AppSetting extends Model
{
    public const MAIL = 'mail';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'encrypted:array',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function mail(): array
    {
        return static::query()->where('key', self::MAIL)->value('value') ?? [];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function storeMail(array $values): void
    {
        static::query()->updateOrCreate(['key' => self::MAIL], ['value' => $values]);
    }
}
