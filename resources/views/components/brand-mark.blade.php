@props(['class' => 'h-6 w-6'])

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
    <defs>
        <linearGradient id="aurex-logo-grad" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
            <stop offset="0%" stop-color="#B7410E"/>
            <stop offset="100%" stop-color="#556B2F"/>
        </linearGradient>
    </defs>
    <rect x="1.5" y="1.5" width="29" height="29" rx="9" stroke="url(#aurex-logo-grad)" stroke-width="1.6"/>
    <path d="M10 22L16 8L22 22" stroke="url(#aurex-logo-grad)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
    <path d="M12.5 17H19.5" stroke="url(#aurex-logo-grad)" stroke-width="1.6" stroke-linecap="round"/>
</svg>
