const colors = require('tailwindcss/colors');

module.exports = {
    content: ['./resources/**/*.{vue,scss}'],
    theme: {
        screens: {
            sm: '480px',
            md: '768px',
            lg: '976px',
            xl: '1440px',
        },
        minHeight: {
            0: '0',
            '4/5': '80vh',
            '3/5': '60vh',
            full: '100%',
            screen: '100vh',
        },
        minWidth: {
            300: '300px',
        },
        colors: {
            white: colors.white,
            gray: colors.gray,
            blue: colors.sky,
            red: colors.rose,
            green: colors.green,
            yellow: colors.yellow,
            orange: colors.orange,
        },
        fontFamily: {
            sans: ['Graphik', 'sans-serif'],
            serif: ['Merriweather', 'serif'],
        },
        boxShadow: {
            md: '0 0.25em .5em -0.125em rgba(85, 85, 85, 0.1), 0 0px 0 1px rgba(85, 85, 85, 0.02)',
        },
        extend: {
            spacing: {
                128: '32rem',
                144: '36rem',
            },
            borderWidth: {
                default: '1px',
                0: '0',
                1: '1px',
                2: '2px',
                4: '4px',
                6: '6px',
            },
            borderRadius: {
                '4xl': '2rem',
            },
            width: {
                sc3: '33vw',
            },
        },
    },
    plugins: [],
};
