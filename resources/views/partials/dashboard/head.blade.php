    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <!-- Google Fonts — single typeface for all admin UI -->
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@400;500;600;700&amp;display=swap" rel="stylesheet"/>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        /* One font everywhere in admin */
        html, body, input, select, textarea, button, table, th, td,
        .font-sans, .font-mono, .font-body-sm, .font-body-md,
        .font-headline-md, .font-title-sm, .font-display-lg,
        .font-label-caps, .font-data-tabular {
            font-family: "Hanken Grotesk", system-ui, -apple-system, sans-serif !important;
        }
        .font-label-caps,
        .text-label-caps {
            letter-spacing: normal;
        }
        /* Custom scrollbar for technical UI density */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

        /* Smooth heights for charts */
        .chart-bar { transition: height 0.8s cubic-bezier(0.4, 0, 0.2, 1); }

        aside {
            font-weight: 400;
        }
        aside .nav-btn,
        aside .nav-btn span:not(.material-symbols-outlined),
        aside .sidebar-text {
            font-family: "Hanken Grotesk", system-ui, sans-serif;
            font-weight: 400;
            letter-spacing: normal;
            text-transform: none;
        }

        /* Responsive admin shell layout constraints */
        body.admin-sidebar-open {
            overflow: hidden;
        }
        @media (min-width: 1024px) {
            body.admin-sidebar-open {
                overflow: auto;
            }
            main, footer {
                max-width: calc(100% - 260px) !important;
                box-sizing: border-box !important;
            }
        }
        @media (max-width: 1023px) {
            main, footer {
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
        }
        #admin-sidebar.admin-sidebar-open {
            transform: translateX(0);
        }
        /* Tables: keep readable padding, scroll horizontally on small screens */
        .admin-table-wrap,
        main .overflow-x-auto {
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
        }
        /* Sticky action columns must sit above scrolled cells */
        table th.sticky,
        table td.sticky {
            position: sticky;
            right: 0;
        }

        /* Customer directory: page-level scroll only — nested sticky tables freeze macOS scrollbars */
        #view-customers .customers-table-wrap {
            overflow-x: auto;
            overflow-y: visible;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
        }
        #view-customers .customers-table {
            min-width: 1080px;
            width: 100%;
            border-collapse: collapse;
        }
        #view-customers .customers-table thead th {
            background: #f2f4f6;
            border-bottom: 1px solid rgba(197, 198, 208, 0.45);
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #44464e;
            white-space: nowrap;
            vertical-align: middle;
        }
        #view-customers .customers-table thead th.sticky-action {
            text-align: right;
        }
        #view-customers .customers-table tbody td {
            padding: 12px 16px;
            vertical-align: top;
            border-bottom: 1px solid rgba(197, 198, 208, 0.22);
            font-size: 13px;
            line-height: 1.35;
            color: #191c1e;
            background: #fff;
        }
        #view-customers .customers-table tbody tr:hover td {
            background: rgba(0, 11, 40, 0.035);
        }
        #view-customers .customers-table tbody td.sticky-action {
            vertical-align: middle;
            text-align: right;
            white-space: nowrap;
        }
        #view-customers .customer-name-link {
            display: inline;
            font-size: 13.5px;
            font-weight: 700;
            color: #000b28;
            text-decoration: none;
            text-underline-offset: 3px;
        }
        #view-customers .customer-name-link:hover {
            color: #994700;
            text-decoration: underline;
        }
        #view-customers .cell-strong {
            font-weight: 600;
            color: #000b28;
            margin: 0;
        }
        #view-customers .cell-meta {
            margin: 2px 0 0;
            font-size: 11px;
            color: #44464e;
        }
        #view-customers .cell-clip {
            margin: 2px 0 0;
            font-size: 11px;
            color: #44464e;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            overflow: hidden;
            word-break: break-word;
        }
        #view-customers .cell-empty {
            color: #c5c6d0;
        }
        #view-customers .discount-chip {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 999px;
            background: #fef3c7;
            color: #92400e;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.5;
        }
        #view-customers .stage-badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            white-space: nowrap;
        }
        #view-customers .assign-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            background: #000b28;
            color: #fff;
            white-space: nowrap;
            border: 0;
            cursor: pointer;
        }
        #view-customers .assign-btn:hover {
            background: #994700;
        }
        #view-customers .empty-row {
            padding: 40px 16px !important;
            text-align: center;
            color: rgba(68, 70, 78, 0.6);
            font-size: 13px;
        }
    </style>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "primary-fixed-dim": "#b4c6f7",
                        "on-primary-container": "#7889b7",
                        "surface-container-high": "#e6e8ea",
                        "inverse-on-surface": "#eff1f3",
                        "surface-container-low": "#f2f4f6",
                        "error": "#ba1a1a",
                        "tertiary-container": "#152436",
                        "surface-container-highest": "#e0e3e5",
                        "secondary-fixed-dim": "#ffb68b",
                        "on-secondary-fixed": "#321200",
                        "tertiary-fixed": "#d5e3fc",
                        "primary": "#000b28",
                        "primary-container": "#0d2149",
                        "tertiary-fixed-dim": "#b9c7df",
                        "inverse-surface": "#2d3133",
                        "primary-fixed": "#d9e2ff",
                        "surface": "#f7f9fb",
                        "tertiary": "#010e1f",
                        "on-surface": "#191c1e",
                        "surface-container-lowest": "#ffffff",
                        "surface-bright": "#f7f9fb",
                        "error-container": "#ffdad6",
                        "background": "#f7f9fb",
                        "outline-variant": "#c5c6d0",
                        "on-primary": "#ffffff",
                        "on-error": "#ffffff",
                        "on-tertiary": "#ffffff",
                        "on-primary-fixed-variant": "#34466f",
                        "secondary": "#994700",
                        "on-secondary-fixed-variant": "#753400",
                        "surface-dim": "#d8dadc",
                        "on-tertiary-container": "#7d8ba1",
                        "inverse-primary": "#b4c6f7",
                        "on-surface-variant": "#44464e",
                        "on-tertiary-fixed": "#0d1c2e",
                        "on-primary-fixed": "#041942",
                        "surface-container": "#eceef0",
                        "surface-variant": "#e0e3e5",
                        "secondary-fixed": "#ffdbc8",
                        "on-background": "#191c1e",
                        "on-error-container": "#93000a",
                        "outline": "#75777f",
                        "on-secondary-container": "#592600",
                        "secondary-container": "#fb7800",
                        "on-tertiary-fixed-variant": "#3a485b",
                        "on-secondary": "#ffffff",
                        "surface-tint": "#4c5d89"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.125rem",
                        "lg": "0.25rem",
                        "xl": "0.5rem",
                        "full": "0.75rem"
                    },
                    "spacing": {
                        "base": "8px",
                        "gutter": "24px",
                        "sm": "8px",
                        "margin-desktop": "32px",
                        "md": "16px",
                        "xs": "4px",
                        "lg": "24px",
                        "xl": "32px",
                        "margin-mobile": "16px"
                    },
                    "fontFamily": {
                        "sans": ["Hanken Grotesk", "system-ui", "sans-serif"],
                        "mono": ["Hanken Grotesk", "system-ui", "sans-serif"],
                        "body-sm": ["Hanken Grotesk", "system-ui", "sans-serif"],
                        "headline-md": ["Hanken Grotesk", "system-ui", "sans-serif"],
                        "title-sm": ["Hanken Grotesk", "system-ui", "sans-serif"],
                        "body-md": ["Hanken Grotesk", "system-ui", "sans-serif"],
                        "display-lg": ["Hanken Grotesk", "system-ui", "sans-serif"],
                        "label-caps": ["Hanken Grotesk", "system-ui", "sans-serif"],
                        "data-tabular": ["Hanken Grotesk", "system-ui", "sans-serif"]
                    },
                    "fontSize": {
                        "body-sm": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "headline-md": ["24px", {"lineHeight": "32px", "letterSpacing": "-0.01em", "fontWeight": "600"}],
                        "title-sm": ["18px", {"lineHeight": "24px", "fontWeight": "600"}],
                        "body-md": ["16px", {"lineHeight": "24px", "fontWeight": "400"}],
                        "display-lg": ["32px", {"lineHeight": "40px", "letterSpacing": "-0.02em", "fontWeight": "700"}],
                        "label-caps": ["12px", {"lineHeight": "16px", "letterSpacing": "normal", "fontWeight": "500"}],
                        "data-tabular": ["14px", {"lineHeight": "20px", "fontWeight": "400"}]
                    }
                },
            },
        }
    </script>
