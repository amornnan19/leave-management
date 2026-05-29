<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('code', 'Error') — @yield('page_title', 'Error')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        /* ── GhostShift Design Tokens ── */
        :root {
            --void:     #06070A;
            --obsidian: #0C0E13;
            --graphite: #14171E;
            --ash:      #262B35;
            --fog:      #7B828F;
            --mist:     #C5CBD4;
            --bone:     #F4F6FA;

            --spectre: #34F5C5;
            --phantom: #6D5DFC;
            --signal:  #FF4D6D;

            --brand-gradient: linear-gradient(135deg, #34F5C5 0%, #6D5DFC 100%);
            --glow-spectre:   0 0 24px rgba(52, 245, 197, 0.45);
            --glow-signal:    0 0 24px rgba(255, 77, 109, 0.45);

            --font-display: 'Space Grotesk', ui-sans-serif, system-ui, -apple-system, sans-serif;
            --font-body:    'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            --font-mono:    'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }

        /* ── Reset ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* ── Base ── */
        html, body {
            height: 100%;
        }

        body {
            background-color: var(--void);
            color: var(--mist);
            font-family: var(--font-body);
            font-size: 1rem;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem 1.25rem;
            overflow-x: hidden;
        }

        /* ── Grain overlay ── */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: 9999;
            pointer-events: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
            background-repeat: repeat;
            background-size: 200px 200px;
            opacity: 0.032;
        }

        /* ── Scanline overlay ── */
        body::after {
            content: '';
            position: fixed;
            inset: 0;
            z-index: 9998;
            pointer-events: none;
            background-image: repeating-linear-gradient(
                0deg,
                transparent,
                transparent 2px,
                rgba(0, 0, 0, 0.025) 2px,
                rgba(0, 0, 0, 0.025) 4px
            );
        }

        /* ── Gradient text ── */
        .text-grad {
            background: var(--brand-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Mono label ── */
        .mono-label {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            opacity: 0.75;
        }

        /* ── Page wrapper ── */
        .error-page {
            position: relative;
            z-index: 1;
            max-width: 640px;
            width: 100%;
            text-align: center;
        }

        /* ── Panel ── */
        .panel {
            background-color: var(--graphite);
            border: 1px solid var(--ash);
            border-radius: 1rem;
            padding: 3rem 2.5rem;
            position: relative;
            overflow: hidden;
        }

        @media (max-width: 480px) {
            .panel {
                padding: 2rem 1.5rem;
            }
        }

        /* ── Radial glow behind number ── */
        .glow-bg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 480px;
            height: 320px;
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .glow-bg.glow-spectre {
            background: radial-gradient(ellipse at center, rgba(52, 245, 197, 0.08) 0%, transparent 70%);
        }

        .glow-bg.glow-signal {
            background: radial-gradient(ellipse at center, rgba(255, 77, 109, 0.08) 0%, transparent 70%);
        }

        /* ── Content above glow ── */
        .panel-inner {
            position: relative;
            z-index: 1;
        }

        /* ── Error code ── */
        .error-code {
            font-family: var(--font-display);
            font-size: clamp(6rem, 22vw, 14rem);
            font-weight: 700;
            letter-spacing: -0.02em;
            line-height: 1;
            display: block;
            margin-bottom: 0;
        }

        .error-code.accent-spectre {
            background: var(--brand-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .error-code.accent-signal {
            background: linear-gradient(135deg, var(--signal) 0%, var(--phantom) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* ── Cursor blink ── */
        .cursor {
            display: inline-block;
            width: 0.6em;
            height: 1em;
            background-color: currentColor;
            vertical-align: text-bottom;
            margin-left: 0.15em;
            border-radius: 2px;
        }

        @media (prefers-reduced-motion: no-preference) {
            .cursor {
                animation: blink 1s step-end infinite;
            }
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0; }
        }

        /* ── Label row ── */
        .error-label {
            margin-top: 1.5rem;
            margin-bottom: 1.75rem;
        }

        .error-label.accent-spectre {
            color: var(--spectre);
        }

        .error-label.accent-signal {
            color: var(--signal);
        }

        /* ── Heading ── */
        h1 {
            font-family: var(--font-display);
            font-size: clamp(1.25rem, 4vw, 1.75rem);
            font-weight: 600;
            letter-spacing: -0.02em;
            color: var(--bone);
            line-height: 1.25;
            margin-bottom: 1rem;
        }

        /* ── Message ── */
        .error-message {
            color: var(--fog);
            font-size: 0.9375rem;
            line-height: 1.7;
            max-width: 480px;
            margin: 0 auto 2.25rem;
        }

        /* ── Divider ── */
        .divider {
            width: 3rem;
            height: 1px;
            background: var(--ash);
            margin: 0 auto 2.25rem;
        }

        /* ── Buttons ── */
        .btn-group {
            display: flex;
            gap: 0.875rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.625rem 1.5rem;
            border-radius: 0.375rem;
            font-family: var(--font-display);
            font-size: 0.875rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, color 0.18s ease;
        }

        .btn-primary {
            background-color: var(--spectre);
            color: var(--void);
            border-color: var(--spectre);
        }

        .btn-primary:hover {
            box-shadow: var(--glow-spectre);
            transform: translateY(-1px);
        }

        .btn-primary.signal {
            background-color: var(--signal);
            border-color: var(--signal);
            color: var(--void);
        }

        .btn-primary.signal:hover {
            box-shadow: var(--glow-signal);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--mist);
            border-color: var(--ash);
        }

        .btn-secondary:hover {
            border-color: var(--spectre);
            color: var(--spectre);
        }

        /* ── Footer tag ── */
        .error-footer {
            margin-top: 2.5rem;
            font-family: var(--font-mono);
            font-size: 0.6875rem;
            color: var(--fog);
            opacity: 0.5;
            letter-spacing: 0.06em;
        }

        @media (prefers-reduced-motion: reduce) {
            .btn {
                transition: none;
            }
        }
    </style>
</head>
<body>

<div class="error-page">
    <div class="panel">
        <div class="glow-bg @yield('glow_class', 'glow-spectre')"></div>
        <div class="panel-inner">

            <span class="error-code @yield('accent_class', 'accent-spectre')">@yield('code')</span>

            <div class="error-label mono-label @yield('accent_class', 'accent-spectre')">
                @yield('label')<span class="cursor"></span>
            </div>

            <h1>@yield('title')</h1>

            <p class="error-message">@yield('message')</p>

            <div class="divider"></div>

            <div class="btn-group">
                @yield('actions')
            </div>

        </div>
    </div>

    <p class="error-footer">{{ \Illuminate\Support\Str::slug(config('app.name')) }}.ghostshift.tech &nbsp;/&nbsp; system-error</p>
</div>

</body>
</html>
