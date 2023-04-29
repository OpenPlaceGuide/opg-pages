/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {}
    },
    safelist: [
        {
            pattern: /bg-|text-|to-|from-|border-|text-/,
        }
    ],
    plugins: [],
}

