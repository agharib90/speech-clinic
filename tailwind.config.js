import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: 'var(--color-primary)',
                    hover: 'var(--color-primary-hover)',
                    soft: 'var(--color-primary-soft)',
                    contrast: 'var(--color-primary-contrast)',
                },
                accent: {
                    DEFAULT: 'var(--color-accent)',
                    hover: 'var(--color-accent-hover)',
                    soft: 'var(--color-accent-soft)',
                    contrast: 'var(--color-accent-contrast)',
                },
                success: {
                    DEFAULT: 'var(--color-success)',
                    hover: 'var(--color-success-hover)',
                    soft: 'var(--color-success-soft)',
                    contrast: 'var(--color-success-contrast)',
                },
                warning: {
                    DEFAULT: 'var(--color-warning)',
                    hover: 'var(--color-warning-hover)',
                    soft: 'var(--color-warning-soft)',
                    contrast: 'var(--color-warning-contrast)',
                },
                danger: {
                    DEFAULT: 'var(--color-danger)',
                    hover: 'var(--color-danger-hover)',
                    soft: 'var(--color-danger-soft)',
                    contrast: 'var(--color-danger-contrast)',
                },
                surface: {
                    DEFAULT: 'var(--color-surface)',
                    elevated: 'var(--color-surface-elevated)',
                    muted: 'var(--color-surface-muted)',
                    border: 'var(--color-surface-border)',
                    overlay: 'var(--color-surface-overlay)',
                },
                text: {
                    DEFAULT: 'var(--color-text)',
                    muted: 'var(--color-text-muted)',
                    subtle: 'var(--color-text-subtle)',
                    inverted: 'var(--color-text-inverted)',
                },
            },
            fontFamily: {
                sans: ['"IBM Plex Sans Arabic"', 'Inter', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
