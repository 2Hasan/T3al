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
     * Amber scale for Filament. 500 is the brand sand (#C9A77C) and fills primary
     * buttons with ink labels (section 3). The darker steps are used for text and
     * focus states that need contrast on light surfaces.
     */
    public const PANEL_PRIMARY = [
        50 => '#FCFAF7',
        100 => '#F7F3ED',
        200 => '#F1E8DD',
        300 => '#E9DCCB',
        400 => '#D9C1A3',
        500 => '#C9A77C',
        600 => '#7D6246',
        700 => '#66503A',
        800 => '#4F3F2D',
        900 => '#382F23',
        950 => '#1E1913',
    ];

    /**
     * Red scale for Filament. 600 is the brand danger (#8B3F35). Filled danger
     * buttons use it with white labels at 6.5:1.
     */
    public const PANEL_DANGER = [
        50 => '#F9F4F3',
        100 => '#F1E6E4',
        200 => '#E5D0CD',
        300 => '#D7B7B2',
        400 => '#BA8079',
        500 => '#A4574C',
        600 => '#8B3F35',
        700 => '#72342C',
        800 => '#5A2924',
        900 => '#421F1A',
        950 => '#2A1210',
    ];
}
