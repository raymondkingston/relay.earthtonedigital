/** @type {import('tailwindcss').Config} */
export default {
    theme: {
        extend: {
            fontFamily: {
                sans: ['Red Hat Text', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [
        forms, 
        require('@tailwindcss/typography'),
    ],
};
