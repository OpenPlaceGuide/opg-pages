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
            pattern: /to-[a-z]*-100|border-[a-z]*-900|text-[a-z]*-900/,
        }
    ],
    plugins: [],
}

