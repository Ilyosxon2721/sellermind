import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/pwa-native.css',
                'resources/js/pwa-detector.js',
                'resources/js/app.js',
                'resources/js/pwa.js',
                'resources/js/pwa/auth.js',
                'resources/js/pwa/haptic.js',
                'resources/js/pwa/cache.js',
                'resources/js/pwa/offline.js',
                'resources/js/pwa/background-sync.js',
                'resources/js/pwa/badge.js',
                'resources/js/pwa/push.js',
                'resources/js/pwa/sw-background-sync.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
        VitePWA({
            registerType: 'autoUpdate',
            includeAssets: ['favicon.svg', 'robots.txt'],
            manifest: {
                name: 'SellerMind - Управление продажами на маркетплейсах',
                short_name: 'SellerMind',
                description: 'Платформа управления продажами на маркетплейсах. Контроль остатков, цен и аналитики в одном окне.',
                theme_color: '#2563eb',
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'portrait-primary',
                scope: '/',
                start_url: '/',
                icons: [
                    {
                        src: '/images/icons/icon-72x72.png',
                        sizes: '72x72',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/images/icons/icon-96x96.png',
                        sizes: '96x96',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/images/icons/icon-128x128.png',
                        sizes: '128x128',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/images/icons/icon-144x144.png',
                        sizes: '144x144',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/images/icons/icon-152x152.png',
                        sizes: '152x152',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/images/icons/icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/images/icons/icon-384x384.png',
                        sizes: '384x384',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/images/icons/icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/images/icons/maskable-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable'
                    }
                ],
                categories: ['business', 'productivity'],
                shortcuts: [
                    {
                        name: "Дашборд",
                        short_name: "Дашборд",
                        description: "Главная панель управления",
                        url: "/dashboard",
                        icons: [{ src: "/images/icons/icon-96x96.png", sizes: "96x96" }]
                    },
                    {
                        name: "Товары",
                        short_name: "Товары",
                        description: "Управление товарами",
                        url: "/marketplace/products",
                        icons: [{ src: "/images/icons/icon-96x96.png", sizes: "96x96" }]
                    },
                    {
                        name: "Заказы",
                        short_name: "Заказы",
                        description: "Просмотр заказов",
                        url: "/marketplace/orders",
                        icons: [{ src: "/images/icons/icon-96x96.png", sizes: "96x96" }]
                    },
                    {
                        name: "Аналитика",
                        short_name: "Аналитика",
                        description: "Аналитика продаж",
                        url: "/analytics",
                        icons: [{ src: "/images/icons/icon-96x96.png", sizes: "96x96" }]
                    }
                ],
                screenshots: [
                    {
                        src: "/images/screenshots/dashboard-wide.png",
                        sizes: "1280x720",
                        type: "image/png",
                        form_factor: "wide",
                        label: "Дашборд SellerMind"
                    },
                    {
                        src: "/images/screenshots/dashboard-narrow.png",
                        sizes: "750x1334",
                        type: "image/png",
                        form_factor: "narrow",
                        label: "Мобильный дашборд"
                    }
                ]
            },
            workbox: {
                // Network First strategy for API calls
                runtimeCaching: [
                    {
                        urlPattern: /^https:\/\/api\./i,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'api-cache',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 60 * 60 // 1 hour
                            },
                            networkTimeoutSeconds: 10
                        }
                    },
                    {
                        urlPattern: /^https:\/\/fonts\./i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'fonts-cache',
                            expiration: {
                                maxEntries: 10,
                                maxAgeSeconds: 60 * 60 * 24 * 365 // 1 year
                            }
                        }
                    },
                    {
                        urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp)$/i,
                        handler: 'CacheFirst',
                        options: {
                            cacheName: 'images-cache',
                            expiration: {
                                maxEntries: 100,
                                maxAgeSeconds: 60 * 60 * 24 * 30 // 30 days
                            }
                        }
                    },
                    // Кэширование API маршрутов приложения
                    {
                        urlPattern: /\/api\/(dashboard|products|orders|analytics)/i,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'sm-api-cache',
                            expiration: {
                                maxEntries: 50,
                                maxAgeSeconds: 60 * 30 // 30 минут
                            },
                            networkTimeoutSeconds: 5,
                            cacheableResponse: {
                                statuses: [0, 200]
                            }
                        }
                    }
                ],
                // Background Sync для отложенных POST/PUT/DELETE запросов
                // Примечание: workbox-background-sync работает внутри Service Worker
                // и автоматически перехватывает неудачные запросы
                // Наша реализация SmBackgroundSync работает на уровне приложения
                // для более гибкого контроля и UI уведомлений
                // Skip API routes from precaching
                navigateFallback: null,
                cleanupOutdatedCaches: true,
                // Дополнительные файлы для precache
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}']
            },
            devOptions: {
                enabled: false, // Disable in development
                type: 'module'
            }
        })
    ],
    build: {
        // Optimize chunk size
        rollupOptions: {
            output: {
                manualChunks: {
                    'alpine': ['alpinejs', '@alpinejs/persist'],
                    'vendor': ['axios'],
                },
            },
        },
        // Increase chunk size warning limit
        chunkSizeWarningLimit: 600,
        // Enable minification
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // Remove console.logs in production
                drop_debugger: true,
            },
        },
        // Enable CSS code splitting
        cssCodeSplit: true,
    },
    // Optimize dependencies
    optimizeDeps: {
        include: ['alpinejs', '@alpinejs/persist', 'axios'],
    },
});
