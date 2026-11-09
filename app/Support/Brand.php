<?php

namespace App\Support;

/**
 * Brand values used by the Filament panels and the font loader.
 * The brand colour tokens live in resources/css/brand-tokens.css.
 */
final class Brand
{
    /**
     * Inter (English) and Tajawal (Arabic). Inter is requested as a variable
     * range, 400 to 900. Tajawal is requested only for the weights it has
     * (400, 500, 700, 800, 900). It has no 600 face, so font-semibold Arabic text
     * is rendered with the 700 face by the browser's weight matching.
     */
    public const FONTS_URL = 'https://fonts.googleapis.com/css2?family=Inter:wght@400..900&family=Tajawal:wght@400;500;700;800;900&display=swap';

    /**
     * Amber scale for Filament. 500 is the brand amber (#F59E0B) and fills primary
     * buttons with ink labels (section 3). The darker steps are used for text and
     * focus states that need contrast on light surfaces.
     */
    public const PANEL_PRIMARY = [
        50 => '#FFFBEB',
        100 => '#FEF3C7',
        200 => '#FDE68A',
        300 => '#FCD34D',
        400 => '#FBBF24',
        500 => '#F59E0B',
        600 => '#B45309',
        700 => '#92400E',
        800 => '#78350F',
        900 => '#5C2A0B',
        950 => '#3A1A06',
    ];

    /**
     * Red scale for Filament. 600 is the brand danger (#B91C1C). Filled danger
     * buttons use it with white labels at 6.5:1.
     */
    public const PANEL_DANGER = [
        50 => '#FEF2F2',
        100 => '#FEE2E2',
        200 => '#FECACA',
        300 => '#FCA5A5',
        400 => '#F87171',
        500 => '#EF4444',
        600 => '#B91C1C',
        700 => '#991B1B',
        800 => '#7F1D1D',
        900 => '#651A1A',
        950 => '#450A0A',
    ];
}
