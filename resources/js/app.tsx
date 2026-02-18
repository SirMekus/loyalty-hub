import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import AppLayout from '@/layouts/app-layout';
import '../css/app.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        // Added because import.meta.glob yields unknown in TypeScript's type system, giving resolvePageComponent no type to infer from
        type PageModule = { default: { layout?: (page: React.ReactNode) => React.ReactNode } };
        const page = await resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ) as PageModule;
        // The  operator means pages that already define their own .layout are left untouched — the default only applies when layout is undefined
        page.default.layout ??= (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
        return page;
    },
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <StrictMode>
                <App {...props} />
            </StrictMode>,
        );
    },
    progress: {
        color: '#4B5563',
    },
});
