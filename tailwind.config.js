import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],

    safelist: [
        // Header gradient colors used dynamically via PHP variables in show.blade.php
        "from-indigo-600",
        "to-indigo-700",
        "from-yellow-500",
        "to-yellow-600",
        "from-indigo-500",
        "to-indigo-600",
        "from-purple-600",
        "to-purple-700",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
