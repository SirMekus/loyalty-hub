import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ReactDOMServer from 'react-dom/server';
import AppLayout from '@/layouts/app-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => (title ? `${title} - ${appName}` : appName),
        resolve: async (name) => {
            // Added because import.meta.glob yields unknown in TypeScript's type system, giving resolvePageComponent no type to infer from
            type PageModule = { default: { layout?: (page: React.ReactNode) => React.ReactNode } };
            const module = await resolvePageComponent(
                `./pages/${name}.tsx`,
                import.meta.glob('./pages/**/*.tsx'),
            ) as PageModule;
            module.default.layout ??= (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
            return module;
        },
        setup: ({ App, props }) => {
            return <App {...props} />;
        },
    }),
);
