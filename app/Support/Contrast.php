<?php

namespace App\Support;

/**
 * WCAG 2.x contrast helpers for shop-chosen colours. Rendering only: the stored
 * values are never changed.
 */
final class Contrast
{
    private const INK = '#1C1917';

    private const WHITE = '#FFFFFF';

    private const BLACK = '#000000';

    /**
     * Return $foreground if it reaches $minimum contrast against $background.
     * Otherwise return the first of ink, white and black that reaches $minimum.
     * Use 4.5 for text and 3.0 for non-text graphics such as stamp outlines.
     * For minimums up to 4.58 (which covers both 4.5 and 3.0) one of the three
     * always reaches the minimum for a hex background.
     * Non-hex input is returned unchanged.
     */
    public static function readable(string $foreground, string $background, float $minimum = 4.5): string
    {
        $ratio = self::ratio($foreground, $background);

        if ($ratio === null || $ratio >= $minimum) {
            return $foreground;
        }

        $best = self::INK;
        $bestRatio = -1.0;

        foreach ([self::INK, self::WHITE, self::BLACK] as $candidate) {
            $candidateRatio = self::ratio($candidate, $background) ?? 0.0;

            if ($candidateRatio >= $minimum) {
                return $candidate;
            }

            if ($candidateRatio > $bestRatio) {
                $best = $candidate;
                $bestRatio = $candidateRatio;
            }
        }

        return $best;
    }

    /** WCAG contrast ratio between two hex colours, or null if either is not hex. */
    public static function ratio(string $a, string $b): ?float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        if ($la === null || $lb === null) {
            return null;
        }

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /** WCAG relative luminance of a hex colour (#rgb or #rrggbb), or null if it is not hex. */
    public static function luminance(string $colour): ?float
    {
        if (! preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $colour, $match)) {
            return null;
        }

        $hex = strlen($match[1]) === 3
            ? preg_replace('/(.)/', '$1$1', $match[1])
            : $match[1];

        $channels = array_map(
            fn (int $offset) => self::linear(hexdec(substr($hex, $offset, 2)) / 255),
            [0, 2, 4],
        );

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    private static function linear(float $channel): float
    {
        return $channel <= 0.04045
            ? $channel / 12.92
            : (($channel + 0.055) / 1.055) ** 2.4;
    }
}
