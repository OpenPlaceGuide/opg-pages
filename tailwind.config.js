/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
    extend: {},
    safelist: [/^bg-/, /^text-/]
  },
  plugins: [],
}

