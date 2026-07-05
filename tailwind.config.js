const defaultTheme = require('tailwindcss/defaultTheme')

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['"Noto Sans"', '"Noto Sans Ethiopic"', ...defaultTheme.fontFamily.sans],
                display: ['"Archivo Variable"', '"Noto Sans Ethiopic"', ...defaultTheme.fontFamily.sans],
            },
            // All theme colors resolve to CSS custom properties defined in
            // app.css (light/dark) and partials/accent.blade.php (per-POI-type).
            colors: {
                paper: 'rgb(var(--paper) / <alpha-value>)',
                ink: 'rgb(var(--ink) / <alpha-value>)',
                surface: 'rgb(var(--surface) / <alpha-value>)',
                soft: 'rgb(var(--soft) / <alpha-value>)',
                edge: 'rgb(var(--edge) / <alpha-value>)',
                star: 'rgb(var(--star) / <alpha-value>)',
                accent: {
                    DEFAULT: 'rgb(var(--accent) / <alpha-value>)',
                    contrast: 'rgb(var(--accent-contrast) / <alpha-value>)',
                    deep: 'rgb(var(--accent-deep) / <alpha-value>)',
                    soft: 'rgb(var(--accent-soft) / <alpha-value>)',
                    text: 'rgb(var(--accent-text) / <alpha-value>)',
                },
            },
        }
    },
    plugins: [],
}
