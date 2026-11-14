<?php

namespace App\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;

/**
 * Draws an avatar from the user's initials, in the brand colours. It is served
 * as an inline SVG, so the panels never depend on an external avatar service.
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model $record): string
    {
        $name = trim((string) ($record->name ?? ''));

        $initials = collect(preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');

        $initials = $initials !== '' ? $initials : '?';

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="32" fill="#F59E0B"/><text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="Inter, Tajawal, sans-serif" font-size="26" font-weight="800" fill="#1C1917">{$initials}</text></svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
