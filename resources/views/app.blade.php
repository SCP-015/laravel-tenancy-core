<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/svg" href="/images/favicon-nusahire.svg"/>
        <link rel="icon" type="image/x-icon" href="/favicon-nusahire.ico"/>
        <title>Nusahire by Nusawork</title>

        <meta name="title" content="Nusahire by Nusawork" />
        <meta property="og:title" content="Nusahire by Nusawork" />
        <meta name="twitter:title" content="Nusahire by Nusawork" />
        <meta name="description" content="Solusi Buat lowongan dengan cepat, Sorting kandidat secara otomatis, dan Tentukan jadwal interview" />
        <meta property="og:description" content="Solusi Buat lowongan dengan cepat, Sorting kandidat secara otomatis, dan Tentukan jadwal interview" />
        <meta name="twitter:description" content="Solusi Buat lowongan dengan cepat, Sorting kandidat secara otomatis, dan Tentukan jadwal interview" />
        <meta name="keywords" content="hiring, nusahire, hire, platform loker, loker, nusa, nusawork, nusanet, hr, hris" />
        <meta name="twitter:image" content="/images/banner.png" />
        <meta property="og:image" content="/images/banner.png" />
        <meta name="twitter:card" content="summary" />
        <meta property="og:url" content="https://nusahire.com" />

        <!-- Preconnect untuk faster resource loading -->
        <link rel="preconnect" href="{{ config('app.url') }}" crossorigin>
        
        <!-- Preload hero image MOBILE FIRST (WebP optimized 900px) -->
        <link rel="preload" href="/images/landing-page-mobile.webp" as="image" type="image/webp" fetchpriority="high" imagesrcset="/images/landing-page-mobile.webp 900w" imagesizes="(max-width: 768px) 100vw, 720px" />
        
        <!-- Critical CSS inline untuk hero section -->
        <style>
            body{margin:0;font-family:system-ui,-apple-system,sans-serif;-webkit-font-smoothing:antialiased}
            .hero-section{background:#fff;padding:1.5rem 1rem;min-height:60vh}
            .hero-container{max-width:72rem;margin:0 auto;display:grid;gap:2rem}
            .hero-title{font-size:1.875rem;font-weight:800;color:#00852C;line-height:1.2;margin:0 0 1rem}
            .hero-subtitle{font-size:0.875rem;color:#4b5563;line-height:1.6;margin-bottom:1.5rem}
            .hero-img-wrapper{display:flex;justify-content:center;margin-top:2rem}
            .hero-img{width:100%;height:auto;border-radius:0.5rem;box-shadow:0 10px 25px rgba(0,0,0,0.1);max-width:36rem}
            @media(min-width:768px){
                .hero-container{grid-template-columns:1fr 1fr;align-items:center;gap:3rem}
                .hero-title{font-size:2.5rem}
                .hero-subtitle{font-size:1rem}
                .hero-img-wrapper{margin-top:0}
            }
        </style>
        
        @vite('resources/js/app.js')
        @inertiaHead
    </head>
    <body>
        @inertia
        {{-- <div id="app"></div> --}}
    </body>
</html>
