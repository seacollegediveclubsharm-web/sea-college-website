<?php
/**
 * Official-style recognizable vector brand & platform SVG icons.
 * Used across footer social bar, Tripadvisor & Google review badges,
 * Multi-Platform Reputation Hub, Contact page, and Promo Banners.
 */
declare(strict_types=1);

function brand_icon(string $name, int $size = 20): string
{
    static $uid = 0;
    $uid++;
    $s = max(12, min(64, $size));
    $attrs = 'width="' . $s . '" height="' . $s . '" aria-hidden="true" focusable="false" class="brand-svg brand-svg--' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"';

    return match ($name) {
        'youtube' => '<svg ' . $attrs . ' viewBox="0 0 24 24">'
            . '<path fill="#FF0000" d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31.4 31.4 0 0 0 0 12a31.4 31.4 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31.4 31.4 0 0 0 24 12a31.4 31.4 0 0 0-.5-5.8z"/>'
            . '<polygon fill="#FFFFFF" points="9.6,15.6 15.8,12 9.6,8.4"/>'
            . '</svg>',

        'tripadvisor' => '<svg ' . $attrs . ' viewBox="0 0 24 24">'
            . '<circle cx="12" cy="12" r="12" fill="#34E0A1"/>'
            . '<path fill="#000000" d="M19.3 9.1h1.6l-1.9 2.1a4.5 4.5 0 1 1-6.5 5.2L12 17.5l-.5-1.1a4.5 4.5 0 1 1-6.5-5.2L3.1 9.1h1.6a8.6 8.6 0 0 1 14.6 0zM7.5 16.4a2.9 2.9 0 1 0 0-5.8 2.9 2.9 0 0 0 0 5.8zm9 0a2.9 2.9 0 1 0 0-5.8 2.9 2.9 0 0 0 0 5.8zm-9-4.4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3zm9 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3zM12 8.2a7 7 0 0 0-4.9 1.9 4.5 4.5 0 0 1 9.8 0A7 7 0 0 0 12 8.2z"/>'
            . '</svg>',

        'google' => '<svg ' . $attrs . ' viewBox="0 0 24 24">'
            . '<path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.4H12v4.5h6.5a5.6 5.6 0 0 1-2.4 3.6v3h3.9c2.3-2.1 3.5-5.2 3.5-8.7z"/>'
            . '<path fill="#34A853" d="M12 24c3.2 0 6-1.1 8-2.9l-3.9-3c-1.1.7-2.5 1.2-4.1 1.2-3.1 0-5.8-2.1-6.7-5H1.3v3.1A12 12 0 0 0 12 24z"/>'
            . '<path fill="#FBBC05" d="M5.3 14.3a7.2 7.2 0 0 1 0-4.6V6.6H1.3a12 12 0 0 0 0 10.8l4-3.1z"/>'
            . '<path fill="#EA4335" d="M12 4.8c1.8 0 3.3.6 4.6 1.8l3.4-3.4A12 12 0 0 0 1.3 6.6l4 3.1c.9-2.9 3.6-4.9 6.7-4.9z"/>'
            . '</svg>',

        'google-maps' => '<svg ' . $attrs . ' viewBox="0 0 24 24">'
            . '<path fill="#EA4335" d="M12 2a7.5 7.5 0 0 0-7.5 7.5c0 5.4 7.5 12.5 7.5 12.5s7.5-7.1 7.5-12.5A7.5 7.5 0 0 0 12 2z"/>'
            . '<circle cx="12" cy="9.5" r="3.2" fill="#FFFFFF"/>'
            . '</svg>',

        'facebook' => '<svg ' . $attrs . ' viewBox="0 0 24 24">'
            . '<circle cx="12" cy="12" r="12" fill="#1877F2"/>'
            . '<path fill="#FFFFFF" d="M16.5 15.3l.5-3.5h-3.3V9.5c0-1 .5-1.9 2-1.9h1.5V4.6s-1.4-.2-2.7-.2c-2.8 0-4.6 1.7-4.6 4.7v2.7H6.8v3.5h3.1V24a12.2 12.2 0 0 0 3.8 0v-8.7h2.8z"/>'
            . '</svg>',

        'instagram' => '<svg ' . $attrs . ' viewBox="0 0 24 24">'
            . '<defs><linearGradient id="igGrad' . $uid . '" x1="0" y1="1" x2="1" y2="0">'
            . '<stop offset="0%" stop-color="#FD5949"/><stop offset="45%" stop-color="#D6249F"/><stop offset="100%" stop-color="#285AEB"/>'
            . '</linearGradient></defs>'
            . '<rect width="24" height="24" rx="6" fill="url(#igGrad' . $uid . ')"/>'
            . '<rect x="3.5" y="3.5" width="17" height="17" rx="4.5" fill="none" stroke="#FFFFFF" stroke-width="1.8"/>'
            . '<circle cx="12" cy="12" r="3.9" fill="none" stroke="#FFFFFF" stroke-width="1.8"/>'
            . '<circle cx="17.3" cy="6.7" r="1.15" fill="#FFFFFF"/>'
            . '</svg>',

        'tiktok' => '<svg ' . $attrs . ' viewBox="0 0 24 24">'
            . '<rect width="24" height="24" rx="6" fill="#0F1419"/>'
            . '<path fill="#25F4EE" d="M16.1 6.2V5.5h-2.5v9.8a2.2 2.2 0 1 1-1.9-2.2v-2.5a4.7 4.7 0 1 0 4.4 4.7V9.9a6.4 6.4 0 0 0 3.6 1.1V8.6a3.9 3.9 0 0 1-3.6-2.4z"/>'
            . '<path fill="#FE2C55" d="M16.8 6.8a3.9 3.9 0 0 0 2.9 1.8v2.4a6.4 6.4 0 0 1-3.6-1.1v5.4a4.7 4.7 0 0 1-7.4 3.8 4.7 4.7 0 0 0 6.7-4.3V5.5h.7a3.9 3.9 0 0 0 .7 1.3z"/>'
            . '<path fill="#FFFFFF" d="M16.1 6.2a3.9 3.9 0 0 0 3.6 2.4v1.8a6.4 6.4 0 0 1-3.6-1.1v5.5a4.7 4.7 0 0 1-8.2 3.1 4.7 4.7 0 0 1 3.8-7.4v2.4a2.2 2.2 0 1 0 1.9 2.2V5.5h2.5v.7z"/>'
            . '</svg>',

        'whatsapp' => '<svg ' . $attrs . ' viewBox="0 0 24 24">'
            . '<circle cx="12" cy="12" r="12" fill="#25D366"/>'
            . '<path fill="#FFFFFF" d="M17.5 14.4c-.3-.1-1.7-.9-2-.9-.3-.1-.5-.2-.7.1-.2.3-.8 1-.9 1.1-.2.2-.3.2-.6.1-.3-.2-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5.3-.5v-.5c-.1-.1-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.7-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.4z"/>'
            . '</svg>',

        'padi' => '<svg ' . $attrs . ' viewBox="0 0 24 24">'
            . '<circle cx="12" cy="12" r="11" fill="#005EB8" stroke="#E31837" stroke-width="1.6"/>'
            . '<ellipse cx="12" cy="12" rx="5.2" ry="9.5" fill="none" stroke="rgba(255,255,255,.55)" stroke-width="1.1"/>'
            . '<line x1="2.5" y1="12" x2="21.5" y2="12" stroke="rgba(255,255,255,.55)" stroke-width="1.1"/>'
            . '<polygon fill="#FFD100" points="12,5.3 13.5,8.7 17.2,9 14.4,11.4 15.3,15 12,13.1 8.7,15 9.6,11.4 6.8,9 10.5,8.7"/>'
            . '</svg>',

        'cdws' => '<svg ' . $attrs . ' viewBox="0 0 24 24">'
            . '<path fill="#0A84A8" d="M12 1.8L3.5 5.4v6.2c0 5.3 3.6 10.2 8.5 11.4 4.9-1.2 8.5-6.1 8.5-11.4V5.4L12 1.8z"/>'
            . '<path fill="none" stroke="#FFFFFF" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M8.3 12.3l2.5 2.5 5.1-5.2"/>'
            . '</svg>',

        default => '',
    };
}

function social_label(string $net): string
{
    return match ($net) {
        'youtube'     => 'YouTube',
        'instagram'   => 'Instagram',
        'facebook'    => 'Facebook',
        'tiktok'      => 'TikTok',
        'tripadvisor' => 'Tripadvisor',
        'whatsapp'    => 'WhatsApp',
        'google'      => 'Google',
        default       => ucfirst($net),
    };
}
