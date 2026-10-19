import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/layouts/app.css',
                'resources/css/dashboard/dashboard.css',
                'resources/css/report-create.css',
                'resources/css/report-show.css',
                'resources/css/auth-login.css',
                'resources/css/discussion.css',
                'resources/css/discussion-index.css',
                'resources/js/auth-login.js',
                'resources/js/app.js'
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
