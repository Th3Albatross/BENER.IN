import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/theme.css',
                'resources/css/layouts/app.css',

                // One stylesheet per page, mirroring resources/views.
                'resources/css/pages/admin.css',
                'resources/css/pages/analytics.css',
                'resources/css/pages/auth-login.css',
                'resources/css/pages/dashboard.css',
                'resources/css/pages/discussion.css',
                'resources/css/pages/discussion-index.css',
                'resources/css/pages/report-form.css',
                'resources/css/pages/report-show.css',
                'resources/css/pages/settings.css',

                'resources/js/analytics.js',
                'resources/js/admin-dashboard.js',
                'resources/js/theme.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
