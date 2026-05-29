@if (request()->routeIs('filament.*.auth.login'))
@php
    $hex = ltrim($accent, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
@endphp
<style>
    /* ── GhostShift ambiance: grain overlay ── */
    .ghostshift-grain {
        position: fixed;
        inset: 0;
        z-index: 9990;
        pointer-events: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");
        background-repeat: repeat;
        background-size: 200px 200px;
        opacity: 0.032;
    }

    /* ── GhostShift ambiance: scanline overlay ── */
    .ghostshift-scanlines {
        position: fixed;
        inset: 0;
        z-index: 9989;
        pointer-events: none;
        background-image: repeating-linear-gradient(
            0deg,
            transparent,
            transparent 2px,
            rgba(0, 0, 0, 0.025) 2px,
            rgba(0, 0, 0, 0.025) 4px
        );
    }

    /* ── GhostShift ambiance: accent radial glow ── */
    .ghostshift-glow {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 600px;
        height: 400px;
        border-radius: 50%;
        pointer-events: none;
        z-index: 9988;
        background: radial-gradient(
            ellipse at center,
            rgba({{ $r }}, {{ $g }}, {{ $b }}, 0.07) 0%,
            transparent 70%
        );
    }
</style>
<div class="ghostshift-grain" aria-hidden="true"></div>
<div class="ghostshift-scanlines" aria-hidden="true"></div>
<div class="ghostshift-glow" aria-hidden="true"></div>
@endif
