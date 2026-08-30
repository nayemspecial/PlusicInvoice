import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, type DefineComponent } from 'vue';

const appName = import.meta.env.VITE_APP_NAME || 'PlusicInvoice';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),

    // Maps an Inertia::render('Auth/Login') call in a controller to
    // resources/js/pages/Auth/Login.vue. Folder name here (lowercase 'pages')
    // must match routes/web.php calls exactly, including case.
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },

    progress: {
        color: '#4B5563',
    },
});
