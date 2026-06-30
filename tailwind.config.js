/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./admin/**/*.php",
    "./admin/**/*.js",
    "./support/**/*.php",
    "./support/**/*.js",
    "./students/**/*.php",
    "./students/**/*.js",
    "./includes/**/*.php",
    "./node_modules/flowbite/**/*.js",
    "./js/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        'dark-backdrop': '#000000',
      }
    },
  },
  plugins: [
    require('flowbite/plugin')
  ],
  darkMode: 'class',
}
