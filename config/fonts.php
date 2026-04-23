<?php

/**
 * Curated Google Fonts for matrimony platforms.
 *
 * 10 hand-picked fonts (5 heading + 5 body) chosen for:
 *   - Matrimony/wedding aesthetic
 *   - Strong readability
 *   - Wide weight support (multiple weights available)
 *   - Good Unicode coverage (supports Indian transliterated names)
 *
 * Each entry lists:
 *   - label:   human-readable name shown in admin UI
 *   - family:  CSS font-family value (must match Google Fonts exactly)
 *   - weights: comma-separated weight list for the Google Fonts URL
 *   - preview: sample text shown in admin previews
 *
 * Admins can ALSO type any Google Font name in a custom-font field on the
 * ThemeBranding page — those are validated only loosely (non-empty string).
 */

return [
    'headings' => [
        'playfair_display' => [
            'label' => 'Playfair Display',
            'family' => 'Playfair Display',
            'weights' => '400;700',
            'preview' => 'Find Your Perfect Match',
        ],
        'cormorant_garamond' => [
            'label' => 'Cormorant Garamond',
            'family' => 'Cormorant Garamond',
            'weights' => '400;500;600;700',
            'preview' => 'A Timeless Love Story',
        ],
        'lora' => [
            'label' => 'Lora',
            'family' => 'Lora',
            'weights' => '400;500;600;700',
            'preview' => 'Where Hearts Meet',
        ],
        'merriweather' => [
            'label' => 'Merriweather',
            'family' => 'Merriweather',
            'weights' => '400;700',
            'preview' => 'Begin Your Journey Together',
        ],
        'dancing_script' => [
            'label' => 'Dancing Script',
            'family' => 'Dancing Script',
            'weights' => '400;500;600;700',
            'preview' => 'A Match Made in Heaven',
        ],
    ],

    'body' => [
        'inter' => [
            'label' => 'Inter',
            'family' => 'Inter',
            'weights' => '400;500;600;700',
            'preview' => 'Search for a life partner who shares your values and dreams.',
        ],
        'poppins' => [
            'label' => 'Poppins',
            'family' => 'Poppins',
            'weights' => '400;500;600;700',
            'preview' => 'Modern, friendly, and widely readable across all devices.',
        ],
        'nunito' => [
            'label' => 'Nunito',
            'family' => 'Nunito',
            'weights' => '400;500;600;700',
            'preview' => 'Soft, rounded, welcoming — perfect for a friendly brand voice.',
        ],
        'dm_sans' => [
            'label' => 'DM Sans',
            'family' => 'DM Sans',
            'weights' => '400;500;700',
            'preview' => 'Geometric, contemporary, and clean at small sizes.',
        ],
        'open_sans' => [
            'label' => 'Open Sans',
            'family' => 'Open Sans',
            'weights' => '400;500;600;700',
            'preview' => 'Universally readable — used by millions of websites worldwide.',
        ],
    ],
];
