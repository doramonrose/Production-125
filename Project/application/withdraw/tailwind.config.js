import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import aspectRatio from '@tailwindcss/aspect-ratio';
import animate from 'tailwindcss-animate';
import headlessUi from '@headlessui/tailwindcss';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['K2D', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                'soft-md': '0 4px 7px -1px rgba(0,0,0,.11), 0 2px 4px -1px rgba(0,0,0,.07)',
                'soft-lg': '0 2px 12px 0 rgba(0,0,0,.16)',
                'soft-xl': '0 20px 27px 0 rgba(0,0,0,.05)',
                'soft-2xl': '0 .3125rem .625rem 0 rgba(0,0,0,.12)',
                'soft-3xl': '0 8px 26px -4px hsla(0,0%,8%,.15), 0 8px 9px -5px hsla(0,0%,8%,.06)',
                'soft-primary-outline': '0 0 0 2px #e9aede',
            },
            transitionDuration: {
                '250': '250ms',
            },
            spacing: {
                '0.38': '0.095rem',
                '1.25': '0.3125rem',
                '1.4': '0.35rem',
                '2.7': '0.675rem',
                '4.5': '1.125rem',
                '5.6': '1.4rem',
                '6.5': '1.625rem',
                '7.5': '1.875rem',
                '8.75': '2.1875rem',
                '11.252': '2.813rem',
                '19.5': '4.875rem',
                '62.5': '15.625rem',
                '68.5': '17.125rem',
                '31/100': '31%',
            },
            maxWidth: {
                '62.5': '15.625rem',
                sidebar: '15.625rem',
            },
            height: {
                '19.5': '4.875rem',
                sidenav: 'calc(100vh - 75px)',
            },
            width: {
                '1/100': '1%',
                '1/10': '10%',
                '3/10': '30%',
                '9/10': '90%',
            },
            lineHeight: {
                pro: '1.4',
                default: '1.6',
                '5.6': '1.4rem',
            },
            backgroundSize: {
                150: '150%',
            },
            backgroundPosition: {
                'x-25': '25%',
            },
            transitionTimingFunction: {
                'nav-brand': 'cubic-bezier(0.25, 0.1, 0.25, 1)',
                soft: 'cubic-bezier(0.25, 0.1, 0.25, 1)',
                'soft-in': 'cubic-bezier(0.42, 0, 1, 1)',
                'soft-out': 'cubic-bezier(0, 0, 0.58, 1)',
                'soft-in-out': 'cubic-bezier(0.42, 0, 0.58, 1)',
            },
            scale: {
                102: '1.02',
            },
            zIndex: {
                '990': '990',
                '1000': '1000',
            },
            animationDelay: {
                '0': '0s',
                '100': '0.1s',
                '200': '0.2s',
                '300': '0.3s',
                '400': '0.4s',
                '500': '0.5s',
            },
        },
    },

    plugins: [forms, typography, aspectRatio, animate, headlessUi],
};
