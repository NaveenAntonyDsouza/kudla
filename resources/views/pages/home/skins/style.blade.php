{{--
    Homepage "skins" over the Classic layout (Oct 2026). Colours and the
    heading font change on the homepage only (.home-skin wraps it); the header,
    footer and every other page keep the site theme.
      tulunadu — kumkum maroon, turmeric gold, mango-leaf green, sandalwood cream
      coastal  — red #B71C1C, orange #F57C00 and cream; secular sunset-over-the-sea artwork (Kudla, Oct 2026)
      parish   — Marian blue, white and gold
--}}
<style>
    .home-skin .trust-signals { margin-top: 26px; }
    /* Wide scene on tablets / desktops; a slim ornament across the top on phones */
    .home-skin .home-art-narrow { display: none; }
    @media (max-width: 767px) {
        .home-skin .home-art-wide { display: none; }
        .home-skin .home-art-narrow { display: block; }
        .home-skin > section:first-of-type { padding-top: 78px; }
    }
</style>
@if($skin === 'tulunadu')
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Kannada:wght@600;700&display=swap" rel="stylesheet">
    <style>
        .home-skin-tulunadu {
            --brand-primary: #8a1c2b; --brand-primary-hover: #6b1420; --brand-primary-light: #fbeee8;
            --brand-secondary: #b4641a; --brand-secondary-hover: #93500f; --brand-secondary-light: #fdf1e2;
            --color-primary: #8a1c2b; --color-primary-hover: #6b1420; --color-primary-light: #fbeee8;
            --color-secondary: #b4641a; --color-secondary-hover: #93500f; --color-secondary-light: #fdf1e2;
            --font-serif: "Noto Serif Kannada", "Playfair Display", Georgia, serif;
            --brand-font-serif: "Noto Serif Kannada", "Playfair Display", Georgia, serif;
            --home-hero-bg: linear-gradient(160deg, #7a1726 0%, #5e111d 55%, #8f4a12 100%);
            --home-band-bg: linear-gradient(135deg, #6b1420 0%, #a35a17 100%);
        }
        .home-skin-tulunadu .bg-gray-50 { background-color: #fbf4e6; }
        .home-skin-tulunadu h2.font-serif { color: #5e111d; }
    </style>
@elseif($skin === 'coastal')
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Kannada:wght@600;700&display=swap" rel="stylesheet">
    <style>
        .home-skin-coastal {
            --brand-primary: #b71c1c; --brand-primary-hover: #8e1515; --brand-primary-light: #ffebee;
            --brand-secondary: #e65100; --brand-secondary-hover: #bf4300; --brand-secondary-light: #fff3e0;
            --color-primary: #b71c1c; --color-primary-hover: #8e1515; --color-primary-light: #ffebee;
            --color-secondary: #e65100; --color-secondary-hover: #bf4300; --color-secondary-light: #fff3e0;
            --font-serif: "Noto Serif Kannada", "Playfair Display", Georgia, serif;
            --brand-font-serif: "Noto Serif Kannada", "Playfair Display", Georgia, serif;
            /* dusk over the Arabian Sea: deep red sky warming towards the horizon */
            --home-hero-bg: linear-gradient(180deg, #5a0e0e 0%, #7d1515 45%, #a63a1c 78%, #c05a22 100%);
            --home-band-bg: linear-gradient(135deg, #8e1515 0%, #b71c1c 100%);
        }
        .home-skin-coastal .bg-gray-50 { background-color: #fbf6ec; }
        .home-skin-coastal h2.font-serif { color: #7f1414; }
    </style>
@elseif($skin === 'parish')
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&display=swap" rel="stylesheet">
    <style>
        .home-skin-parish {
            --brand-primary: #1f3a6b; --brand-primary-hover: #152a50; --brand-primary-light: #eef2fa;
            --brand-secondary: #a8832f; --brand-secondary-hover: #8a6a22; --brand-secondary-light: #f8f1e1;
            --color-primary: #1f3a6b; --color-primary-hover: #152a50; --color-primary-light: #eef2fa;
            --color-secondary: #a8832f; --color-secondary-hover: #8a6a22; --color-secondary-light: #f8f1e1;
            --font-serif: "Cormorant Garamond", "Playfair Display", Georgia, serif;
            --brand-font-serif: "Cormorant Garamond", "Playfair Display", Georgia, serif;
            --home-hero-bg: linear-gradient(160deg, #1d3768 0%, #142a52 60%, #27457a 100%);
            --home-band-bg: linear-gradient(135deg, #152a50 0%, #2a4a80 100%);
        }
        .home-skin-parish .bg-gray-50 { background-color: #f7f5ef; }
        .home-skin-parish h2.font-serif { color: #152a50; letter-spacing: 0.01em; }
        .home-skin-parish .parish-verse { margin-top: 14px; font-family: "Cormorant Garamond", Georgia, serif; font-style: italic; font-size: 1.15rem; color: #f3dfa6; }
    </style>
@endif
