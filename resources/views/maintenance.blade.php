@php
    $generalSettings = app(\App\Settings\GeneralSettings::class);
    $maintenanceSettings = app(\App\Settings\MaintenanceSettings::class);
@endphp
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />

    <!-- Resource Hints -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin />

    <!-- Preload critical resources -->
    <link rel="preload" href="{{ asset('maintenance/css/style.css') }}" as="style" />
    <link rel="preload" href="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js" as="script" />
    @if ($generalSettings->site_logo)
        <link rel="preload" href="{{ Storage::url($generalSettings->site_logo) }}" as="image" type="image/png" />
    @endif
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap"
        as="style" />

    <!-- Title + Canonical -->
    <title>🚧 {{ $maintenanceSettings->title }} - {{ $generalSettings->site_name }}</title>
    <link rel="canonical" href="{{ url('/') }}" />

    <!-- Language alternates -->
    <link rel="alternate" hreflang="id" href="{{ url('/') }}" />
    <link rel="alternate" hreflang="x-default" href="{{ url('/') }}" />

    <!-- SEO Meta -->
    <meta name="description" content="{{ $maintenanceSettings->message }}" />
    <meta name="keywords"
        content="maintenance, upgrade, perbaikan sistem, coming soon, {{ $generalSettings->site_name }}" />
    <meta name="author" content="{{ $generalSettings->site_name }}" />
    <meta name="robots" content="noindex, nofollow" />

    <!-- Open Graph (Facebook, WhatsApp, etc.) -->
    <meta property="og:title" content="🚧 {{ $maintenanceSettings->title }} - {{ $generalSettings->site_name }}" />
    <meta property="og:description" content="{{ $maintenanceSettings->message }}" />
    <meta property="og:url" content="{{ url('/') }}" />
    <meta property="og:type" content="website" />
    @if ($generalSettings->site_logo)
        <meta property="og:image" content="{{ Storage::url($generalSettings->site_logo) }}" />
        <meta property="og:image:alt" content="Logo {{ $generalSettings->site_name }}" />
    @endif
    <meta property="og:site_name" content="{{ $generalSettings->site_name }}" />
    <meta property="og:locale" content="id_ID" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="🚧 {{ $maintenanceSettings->title }} - {{ $generalSettings->site_name }}" />
    <meta name="twitter:description" content="{{ $maintenanceSettings->message }}" />
    @if ($generalSettings->site_logo)
        <meta name="twitter:image" content="{{ Storage::url($generalSettings->site_logo) }}" />
        <meta name="twitter:image:alt" content="Logo {{ $generalSettings->site_name }}" />
    @endif

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "{{ $generalSettings->site_name }}",
        "url": "{{ url('/') }}",
        "description": "{{ $maintenanceSettings->message }}",
        "mainEntityOfPage": {
            "@type": "WebPage",
            "mainContentOfPage": {
                "@type": "SpecialAnnouncement",
                "name": "{{ $maintenanceSettings->title }}",
                "text": "{{ $maintenanceSettings->message }}",
                "category": "https://schema.org/BusinessClosure",
                "datePosted": "{{ now()->toIso8601String() }}"
            }
        }
    }
    </script>

    <!-- Mobile + Theming -->
    <meta name="theme-color" content="{{ $maintenanceSettings->theme_color }}" />
    <meta name="mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
    <meta name="application-name" content="{{ $generalSettings->site_name }}" />
    <meta name="apple-mobile-web-app-title" content="{{ $generalSettings->site_name }}" />

    <!-- Favicon -->
    @if ($generalSettings->site_favicon)
        <link rel="icon" href="{{ Storage::url($generalSettings->site_favicon) }}" type="image/x-icon" />
        <link rel="apple-touch-icon" href="{{ Storage::url($generalSettings->site_favicon) }}" />
    @endif

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet"
        media="print" onload="this.media='all'" />
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet" />
    </noscript>

    <!-- Stylesheets -->
    <link href="{{ asset('maintenance/css/style.css') }}" rel="stylesheet" />

    <style>
        :root {
            --theme-color: {{ $maintenanceSettings->theme_color }};
        }
    </style>
</head>

<body>
    <div id="particles" class="particles" aria-hidden="true"></div>
    <div class="glitch-overlay" aria-hidden="true"></div>

    <main class="container">
        <section class="card" role="region" aria-labelledby="maintenance-title">
            @if ($maintenanceSettings->show_countdown && $maintenanceSettings->end_time)
                <div class="countdown" id="countdown" role="timer" aria-live="polite"
                    data-end-time="{{ $maintenanceSettings->end_time }}">
                    <div class="time-block"><span id="days">00</span><span class="label">Hari</span></div>
                    <div class="time-block"><span id="hours">00</span><span class="label">Jam</span></div>
                    <div class="time-block"><span id="minutes">00</span><span class="label">Menit</span></div>
                    <div class="time-block"><span id="seconds">00</span><span class="label">Detik</span></div>
                </div>
            @endif

            <div class="message">
                @if ($generalSettings->site_logo)
                    <img src="{{ Storage::url($generalSettings->site_logo) }}"
                        alt="Logo {{ $generalSettings->site_name }}"
                        style="max-width: 120px; margin-bottom: 20px;" />
                @endif
                <h1 id="maintenance-title">{{ $maintenanceSettings->title }}</h1>
                <p>{{ $maintenanceSettings->message }}</p>
            </div>

            @if ($maintenanceSettings->show_progress)
                <div class="progress-container" style="margin: 30px 0;">
                    <div class="progress-bar"
                        style="width: 100%; height: 10px; background: rgba(255,255,255,0.1); border-radius: 5px; overflow: hidden;">
                        <div class="progress-fill"
                            style="width: {{ $maintenanceSettings->progress_percentage }}%; height: 100%; background: linear-gradient(90deg, {{ $maintenanceSettings->theme_color }}, #00ff88); transition: width 0.3s;">
                        </div>
                    </div>
                    <p style="text-align: center; margin-top: 10px; font-size: 14px; opacity: 0.8;">Progress:
                        {{ $maintenanceSettings->progress_percentage }}%</p>
                </div>
            @endif

            @if ($maintenanceSettings->contact_email)
                <div class="contact-info"
                    style="margin: 20px 0; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 10px; text-align: center;">
                    <p style="margin: 0; font-size: 14px;">Butuh bantuan? Hubungi kami:</p>
                    <a href="mailto:{{ $maintenanceSettings->contact_email }}"
                        style="color: {{ $maintenanceSettings->theme_color }}; text-decoration: none; font-weight: 600;">
                        {{ $maintenanceSettings->contact_email }}
                    </a>
                </div>
            @endif

            @if ($maintenanceSettings->show_social_links)
                <div class="buttons">
                    <div class="social-buttons">
                        <a href="https://linkedin.com/company/your-company" class="btn social"
                            rel="noopener noreferrer" aria-label="LinkedIn" target="_blank">
                            <i data-feather="linkedin" aria-hidden="true"></i>
                        </a>
                        <a href="https://instagram.com/your-instagram" class="btn social" rel="noopener noreferrer"
                            aria-label="Instagram" target="_blank">
                            <i data-feather="instagram" aria-hidden="true"></i>
                        </a>
                        <a href="https://github.com/your-github" class="btn social" rel="noopener noreferrer"
                            aria-label="GitHub" target="_blank">
                            <i data-feather="github" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            @endif
        </section>
    </main>

    <footer>
        <div class="footer-content">
            <p>&copy; {{ date('Y') }} {{ $generalSettings->site_name }}</p>
            <p>Jam: <span id="time">{{ now()->format('H:i:s') }}</span></p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" defer></script>
    <script src="https://unpkg.com/feather-icons" defer></script>
    <script src="{{ asset('maintenance/js/app.js') }}" defer></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mark particles as loaded
            document.getElementById('particles').classList.add('loaded');

            @if ($maintenanceSettings->show_countdown && $maintenanceSettings->end_time)
                // Countdown timer
                const endTime = new Date('{{ $maintenanceSettings->end_time }}').getTime();

                function updateCountdown() {
                    const now = new Date().getTime();
                    const distance = endTime - now;

                    if (distance < 0) {
                        document.getElementById('countdown').innerHTML = '<p>Maintenance selesai!</p>';
                        return;
                    }

                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    document.getElementById('days').innerText = String(days).padStart(2, '0');
                    document.getElementById('hours').innerText = String(hours).padStart(2, '0');
                    document.getElementById('minutes').innerText = String(minutes).padStart(2, '0');
                    document.getElementById('seconds').innerText = String(seconds).padStart(2, '0');
                }

                updateCountdown();
                setInterval(updateCountdown, 1000);
            @endif

            // Update clock
            function updateClock() {
                const now = new Date();
                document.getElementById('time').innerText = now.toLocaleTimeString('id-ID');
            }
            setInterval(updateClock, 1000);

            // Initialize Feather icons
            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        });
    </script>

    <!-- Noscript fallback -->
    <noscript>
        <div class="noscript-message">
            <p>Situs ini membutuhkan JavaScript untuk tampilan optimal. Silakan aktifkan JavaScript di browser Anda.</p>
        </div>
    </noscript>
</body>

</html>
