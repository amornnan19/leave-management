<style>
    /* ── GhostShift brand header ── */
    .ghostshift-brand {
        text-align: center;
        margin-bottom: 1.25rem;
    }

    .ghostshift-label {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, 'Courier New', monospace;
        font-size: 0.6875rem;
        font-weight: 500;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: {{ $accent }};
        display: inline-block;
    }

    .ghostshift-cursor {
        display: inline-block;
        width: 0.55em;
        height: 0.9em;
        background-color: {{ $accent }};
        vertical-align: text-bottom;
        margin-left: 0.1em;
        border-radius: 1px;
        opacity: 0.85;
    }

    @media (prefers-reduced-motion: no-preference) {
        .ghostshift-cursor {
            animation: ghostshift-blink 1s step-end infinite;
        }
    }

    @keyframes ghostshift-blink {
        0%, 100% { opacity: 0.85; }
        50%       { opacity: 0; }
    }

    .ghostshift-divider {
        margin-top: 0.75rem;
        height: 1px;
        background: linear-gradient(90deg, transparent, {{ $accent }}, transparent);
        opacity: 0.35;
        border: none;
    }
</style>
<div class="ghostshift-brand">
    <span class="ghostshift-label">GHOSTSHIFT // SECURE ACCESS<span class="ghostshift-cursor" aria-hidden="true"></span></span>
    <div class="ghostshift-divider" role="separator" aria-hidden="true"></div>
</div>
