<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SellerMind — Платформа управления продажами на маркетплейсах</title>
    <meta name="description" content="Профессиональная платформа для управления продажами на маркетплейсах. Автоматизация, аналитика, складской учёт.">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        
        /* Improved Fullpage Scroll Snap */
        html {
            scroll-behavior: smooth;
        }
        
        body {
            scroll-snap-type: y mandatory;
            overflow-y: scroll;
            overflow-x: hidden;
            scroll-padding-top: 0;
        }
        
        section {
            scroll-snap-align: start;
            scroll-snap-stop: normal;
        }
        
        /* Scroll Reveal Animations - Faster */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.4s cubic-bezier(0.4, 0, 0.2, 1), 
                        transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .scroll-reveal.revealed {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Stagger animation for cards - Shorter delays */
        .scroll-reveal-card {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.35s cubic-bezier(0.4, 0, 0.2, 1), 
                        transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .scroll-reveal-card.revealed {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Faster stagger delays */
        .scroll-reveal-card:nth-child(1).revealed { transition-delay: 0.05s; }
        .scroll-reveal-card:nth-child(2).revealed { transition-delay: 0.1s; }
        .scroll-reveal-card:nth-child(3).revealed { transition-delay: 0.15s; }
        .scroll-reveal-card:nth-child(4).revealed { transition-delay: 0.2s; }
        .scroll-reveal-card:nth-child(5).revealed { transition-delay: 0.25s; }
        .scroll-reveal-card:nth-child(6).revealed { transition-delay: 0.3s; }
        
        /* Smooth hover effects */
        .hover-lift {
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1), 
                        box-shadow 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .hover-lift:hover {
            transform: translateY(-5px);
        }
        
        /* Section Indicators - Smoother */
        .section-indicators {
            position: fixed;
            right: 2rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 100;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .section-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #cbd5e1;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
        }
        
        .section-indicator:hover {
            background: #94a3b8;
            transform: scale(1.2);
        }
        
        .section-indicator.active {
            background: #2563eb;
            transform: scale(1.3);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
        }
        
        @media (max-width: 768px) {
            .section-indicators {
                display: none;
            }
        }
    </style>
</head>
<body class="antialiased bg-white text-gray-900" x-data="{ mobileMenuOpen: false, faqOpen: null }">

    <!-- Header - White -->
    <header class="bg-white border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <span class="font-bold text-xl text-gray-900">SellerMind</span>
                </div>
                
                <nav class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-600 hover:text-blue-600 transition">{{ __('landing.nav_features') }}</a>
                    <a href="#integrations" class="text-gray-600 hover:text-blue-600 transition">{{ __('landing.nav_integrations') }}</a>
                    <a href="#pricing" class="text-gray-600 hover:text-blue-600 transition">{{ __('landing.nav_pricing') }}</a>
                    <a href="#faq" class="text-gray-600 hover:text-blue-600 transition">{{ __('landing.nav_faq') }}</a>
                    
                    <!-- Language Switcher in Navbar -->
                    <div class="relative" x-data="{ open: false }">
                        <button 
                            @click="open = !open" 
                            class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100 transition"
                        >
                            <span class="text-lg">
                                @if(app()->getLocale() === 'uz') 🇺🇿
                                @elseif(app()->getLocale() === 'ru') 🇷🇺
                                @else 🇬🇧
                                @endif
                            </span>
                            <svg class="w-4 h-4 text-gray-500 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <div 
                            x-show="open" 
                            @click.away="open = false"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform scale-95"
                            x-transition:enter-end="opacity-100 transform scale-100"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 transform scale-100"
                            x-transition:leave-end="opacity-0 transform scale-95"
                            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 overflow-hidden"
                            style="display: none;"
                        >
                            <a href="{{ route('home.uz') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50 transition {{ app()->getLocale() === 'uz' ? 'bg-blue-50' : '' }}">
                                <span class="text-2xl">🇺🇿</span>
                                <span class="font-medium text-gray-700">O'zbekcha</span>
                                @if(app()->getLocale() === 'uz')
                                    <svg class="w-5 h-5 ml-auto text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </a>
                            <a href="{{ route('home.ru') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50 transition {{ app()->getLocale() === 'ru' ? 'bg-blue-50' : '' }}">
                                <span class="text-2xl">🇷🇺</span>
                                <span class="font-medium text-gray-700">Русский</span>
                                @if(app()->getLocale() === 'ru')
                                    <svg class="w-5 h-5 ml-auto text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </a>
                            <a href="{{ route('home.en') }}" class="flex items-center gap-3 px-4 py-3 hover:bg-blue-50 transition {{ app()->getLocale() === 'en' ? 'bg-blue-50' : '' }}">
                                <span class="text-2xl">🇬🇧</span>
                                <span class="font-medium text-gray-700">English</span>
                                @if(app()->getLocale() === 'en')
                                    <svg class="w-5 h-5 ml-auto text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </a>
                        </div>
                    </div>
                    
                    <a href="/login" class="text-gray-600 hover:text-blue-600 transition">{{ __('landing.nav_login') }}</a>
                    <a href="/register" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold">{{ __('landing.nav_register') }}</a>
                </nav>
                
                
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden p-2 text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div x-show="mobileMenuOpen" x-cloak class="lg:hidden bg-white border-t border-gray-100">
            <div class="px-4 py-4 space-y-2">
                <a href="#features" class="block py-2 text-gray-600">Возможности</a>
                <a href="#how-it-works" class="block py-2 text-gray-600">Как работает</a>
                <a href="#integrations" class="block py-2 text-gray-600">Интеграции</a>
                <a href="#testimonials" class="block py-2 text-gray-600">Отзывы</a>
                <a href="#pricing" class="block py-2 text-gray-600">Тарифы</a>
                <a href="#faq" class="block py-2 text-gray-600">FAQ</a>
                <div class="pt-4 border-t border-gray-100 flex gap-3">
                    <a href="/login" class="flex-1 py-2 text-center text-gray-600 border border-gray-200 rounded-lg">Вход</a>
                    <a href="/register" class="flex-1 py-2 text-center bg-blue-600 rounded-lg font-medium" style="color: white !important;">Регистрация</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Hero Section - Light Blue Gradient -->
    <section class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 py-20 lg:py-28">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-4xl mx-auto">
                <div class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm font-medium mb-6">
                    <span class="w-2 h-2 bg-blue-600 rounded-full mr-2 animate-pulse"></span>
                    Uzum • Wildberries • Ozon • Yandex Market
                </div>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight mb-6">
                    {{ __('landing.hero_title') }} <span class="text-blue-600">{{ __('landing.hero_title_highlight') }}</span> {{ __('landing.hero_title_end') }}
                </h1>
                <p class="text-xl text-gray-700 mb-8 max-w-2xl mx-auto">
                    {{ __('landing.hero_subtitle') }}
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                    <a href="/register" class="px-8 py-4 bg-blue-600 font-semibold rounded-xl hover:bg-blue-700 transition text-lg shadow-lg shadow-blue-600/30" style="color: white !important;">
                        {{ __('landing.hero_cta_primary') }}
                    </a>
                    <a href="#demo" class="px-8 py-4 bg-white text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition text-lg border border-gray-200">
                        {{ __('landing.hero_cta_secondary') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-white border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-8">
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2">500+</div>
                    <div class="text-gray-600">{{ __('landing.stat_companies') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2">50K+</div>
                    <div class="text-gray-600">{{ __('landing.stat_products') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2">$2M+</div>
                    <div class="text-gray-600">{{ __('landing.stat_processed') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-blue-600 mb-2">99.9%</div>
                    <div class="text-gray-600">{{ __('landing.stat_uptime') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-green-600 mb-2">70%</div>
                    <div class="text-gray-600">{{ __('landing.stat_time_saved') }}</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-orange-600 mb-2">-25%</div>
                    <div class="text-gray-600">{{ __('landing.stat_dead_stock') }}</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 scroll-reveal">
                <div class="text-sm font-semibold text-blue-600 uppercase tracking-wide mb-2">{{ __('landing.nav_features') }}</div>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">{{ __('landing.features_title') }}</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">{{ __('landing.features_subtitle') }}</p>
            </div>
            
            <!-- Product Management -->
            <div class="mb-12 scroll-reveal">
                <h3 class="text-xl font-bold text-gray-900 mb-6 px-4">{{ __('landing.category_products') }}</h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all group scroll-reveal-card hover-lift">
                        <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-blue-600 group-hover:text-white transition">📦</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('landing.feature_sync_title') }}</h3>
                        <p class="text-gray-600">{{ __('landing.feature_sync_desc') }}</p>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all group scroll-reveal-card hover-lift">
                        <div class="w-14 h-14 bg-purple-100 text-purple-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-purple-600 group-hover:text-white transition">⚡</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('landing.feature_bulk_title') }}</h3>
                        <p class="text-gray-600">{{ __('landing.feature_bulk_desc') }}</p>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all group scroll-reveal-card hover-lift">
                        <div class="w-14 h-14 bg-indigo-100 text-indigo-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-indigo-600 group-hover:text-white transition">📋</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('landing.feature_catalog_title') }}</h3>
                        <p class="text-gray-600">{{ __('landing.feature_catalog_desc') }}</p>
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-gray-900 mb-6 px-4">{{ __('landing.category_pricing') }}</h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all group scroll-reveal-card hover-lift">
                        <div class="w-14 h-14 bg-green-100 text-green-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-green-600 group-hover:text-white transition">💰</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('landing.feature_pricing_title') }}</h3>
                        <p class="text-gray-600">{{ __('landing.feature_pricing_desc') }}</p>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all group scroll-reveal-card hover-lift">
                        <div class="w-14 h-14 bg-pink-100 text-pink-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-pink-600 group-hover:text-white transition">🎯</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('landing.feature_promo_title') }}</h3>
                        <p class="text-gray-600">{{ __('landing.feature_promo_desc') }}</p>
                    </div>
                </div>
            </div>

            <!-- Warehouse -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-gray-900 mb-6 px-4">{{ __('landing.category_warehouse') }}</h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all group scroll-reveal-card hover-lift">
                        <div class="w-14 h-14 bg-yellow-100 text-yellow-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-yellow-600 group-hover:text-white transition">📊</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('landing.feature_wms_title') }}</h3>
                        <p class="text-gray-600">{{ __('landing.feature_wms_desc') }}</p>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all group scroll-reveal-card hover-lift">
                        <div class="w-14 h-14 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-red-600 group-hover:text-white transition">📝</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('landing.feature_movements_title') }}</h3>
                        <p class="text-gray-600">{{ __('landing.feature_movements_desc') }}</p>
                    </div>
                </div>
            </div>

            <!-- Orders & Analytics -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-gray-900 mb-6 px-4">{{ __('landing.category_orders') }}</h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all group scroll-reveal-card hover-lift">
                        <div class="w-14 h-14 bg-cyan-100 text-cyan-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-cyan-600 group-hover:text-white transition">🛒</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('landing.feature_orders_title') }}</h3>
                        <p class="text-gray-600">{{ __('landing.feature_orders_desc') }}</p>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all group scroll-reveal-card hover-lift">
                        <div class="w-14 h-14 bg-teal-100 text-teal-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-teal-600 group-hover:text-white transition">📈</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('landing.feature_analytics_title') }}</h3>
                        <p class="text-gray-600">{{ __('landing.feature_analytics_desc') }}</p>
                    </div>
                </div>
            </div>

            <!-- AI Features -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-gray-900 mb-6 px-4">{{ __('landing.category_ai') }}</h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all group scroll-reveal-card hover-lift">
                        <div class="w-14 h-14 bg-violet-100 text-violet-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-violet-600 group-hover:text-white transition">🤖</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('landing.feature_ai_title') }}</h3>
                        <p class="text-gray-600">{{ __('landing.feature_ai_desc') }}</p>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all group scroll-reveal-card hover-lift">
                        <div class="w-14 h-14 bg-fuchsia-100 text-fuchsia-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-fuchsia-600 group-hover:text-white transition">💬</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('landing.feature_ai_reviews_title') }}</h3>
                        <p class="text-gray-600">{{ __('landing.feature_ai_reviews_desc') }}</p>
                    </div>
                </div>
            </div>

            <!-- Additional Features -->
            <div class="mb-12">
                <h3 class="text-xl font-bold text-gray-900 mb-6 px-4">{{ __('landing.category_additional') }}</h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all group scroll-reveal-card hover-lift">
                        <div class="w-14 h-14 bg-sky-100 text-sky-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-sky-600 group-hover:text-white transition">📱</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('landing.feature_telegram_title') }}</h3>
                        <p class="text-gray-600">{{ __('landing.feature_telegram_desc') }}</p>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all group scroll-reveal-card hover-lift">
                        <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-emerald-600 group-hover:text-white transition">💳</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('landing.feature_finance_title') }}</h3>
                        <p class="text-gray-600">{{ __('landing.feature_finance_desc') }}</p>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all group scroll-reveal-card hover-lift">
                        <div class="w-14 h-14 bg-amber-100 text-amber-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-amber-600 group-hover:text-white transition">🔮</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('landing.feature_forecast_title') }}</h3>
                        <p class="text-gray-600">{{ __('landing.feature_forecast_desc') }}</p>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 hover:border-blue-200 hover:shadow-xl transition-all group scroll-reveal-card hover-lift">
                        <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center text-2xl mb-6 group-hover:bg-rose-600 group-hover:text-white transition">👥</div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ __('landing.feature_team_title') }}</h3>
                        <p class="text-gray-600">{{ __('landing.feature_team_desc') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How it Works -->
    <section id="how-it-works" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <div class="text-sm font-semibold text-blue-600 uppercase tracking-wide mb-2">Как это работает</div>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Начните работу за 5 минут</h2>
                <p class="text-lg text-gray-600">Простой процесс подключения</p>
            </div>
            
            <div class="grid md:grid-cols-4 gap-8">
                <div class="text-center relative">
                    <div class="w-16 h-16 bg-blue-600 text-white rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto mb-4 shadow-lg">1</div>
                    <h3 class="font-bold text-gray-900 mb-2">Регистрация</h3>
                    <p class="text-gray-600 text-sm">Создайте аккаунт за минуту. Без банковской карты.</p>
                    <div class="hidden md:block absolute top-8 left-full w-full h-0.5 bg-gray-200 -translate-x-1/2"></div>
                </div>
                <div class="text-center relative">
                    <div class="w-16 h-16 bg-blue-600 text-white rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto mb-4 shadow-lg">2</div>
                    <h3 class="font-bold text-gray-900 mb-2">Подключение</h3>
                    <p class="text-gray-600 text-sm">Подключите маркетплейсы через API-ключи.</p>
                    <div class="hidden md:block absolute top-8 left-full w-full h-0.5 bg-gray-200 -translate-x-1/2"></div>
                </div>
                <div class="text-center relative">
                    <div class="w-16 h-16 bg-blue-600 text-white rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto mb-4 shadow-lg">3</div>
                    <h3 class="font-bold text-gray-900 mb-2">Синхронизация</h3>
                    <p class="text-gray-600 text-sm">Данные загрузятся автоматически.</p>
                    <div class="hidden md:block absolute top-8 left-full w-full h-0.5 bg-gray-200 -translate-x-1/2"></div>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-500 text-white rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto mb-4 shadow-lg">✓</div>
                    <h3 class="font-bold text-gray-900 mb-2">Готово!</h3>
                    <p class="text-gray-600 text-sm">Управляйте бизнесом из одного окна.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div class="text-sm font-semibold text-blue-600 uppercase tracking-wide mb-2">Преимущества</div>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-6">Почему выбирают SellerMind</h2>
                    
                    <div class="space-y-6">
                        <div class="flex gap-4">
                            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center flex-shrink-0">✓</div>
                            <div>
                                <h3 class="font-bold text-gray-900 mb-1">Экономия времени</h3>
                                <p class="text-gray-600">Автоматизация рутинных задач экономит до 20 часов в неделю.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center flex-shrink-0">✓</div>
                            <div>
                                <h3 class="font-bold text-gray-900 mb-1">Без пересортов</h3>
                                <p class="text-gray-600">Синхронизация остатков исключает продажу несуществующих товаров.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center flex-shrink-0">✓</div>
                            <div>
                                <h3 class="font-bold text-gray-900 mb-1">Рост продаж</h3>
                                <p class="text-gray-600">AI-оптимизация карточек увеличивает конверсию на 15-30%.</p>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center flex-shrink-0">✓</div>
                            <div>
                                <h3 class="font-bold text-gray-900 mb-1">Полный контроль</h3>
                                <p class="text-gray-600">Все данные, аналитика и управление в одном месте.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-blue-50 to-indigo-100 rounded-3xl p-8">
                    <div class="bg-white rounded-2xl p-6 shadow-xl">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                            </div>
                            <span class="font-bold text-gray-900">Рост среднего клиента</span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <div class="text-3xl font-bold text-blue-600">+40%</div>
                                <div class="text-sm text-gray-600">Продажи</div>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <div class="text-3xl font-bold text-green-600">-70%</div>
                                <div class="text-sm text-gray-600">Время на рутину</div>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <div class="text-3xl font-bold text-purple-600">0</div>
                                <div class="text-sm text-gray-600">Пересортов</div>
                            </div>
                            <div class="bg-gray-50 rounded-xl p-4 text-center">
                                <div class="text-3xl font-bold text-orange-600">24/7</div>
                                <div class="text-sm text-gray-600">Мониторинг</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Business Impact Section - NEW -->
    <section class="py-20 bg-gradient-to-br from-blue-50 via-white to-indigo-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 scroll-reveal">
                <div class="text-sm font-semibold text-blue-600 uppercase tracking-wide mb-2">{{ __('landing.testimonials_title') }}</div>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">{{ __('landing.impact_title') }}</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">{{ __('landing.impact_subtitle') }}</p>
            </div>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all scroll-reveal-card hover-lift">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-5xl font-bold text-green-600">+40%</div>
                        <div class="w-14 h-14 bg-green-100 rounded-2xl flex items-center justify-center text-2xl">📈</div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('landing.impact_sales') }}</h3>
                    <p class="text-gray-600">{{ __('landing.impact_sales_desc') }}</p>
                </div>
                
                <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all scroll-reveal-card hover-lift">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-5xl font-bold text-blue-600">-70%</div>
                        <div class="w-14 h-14 bg-blue-100 rounded-2xl flex items-center justify-center text-2xl">⏱️</div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('landing.impact_time') }}</h3>
                    <p class="text-gray-600">{{ __('landing.impact_time_desc') }}</p>
                </div>
                
                <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all scroll-reveal-card hover-lift">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-5xl font-bold text-purple-600">0</div>
                        <div class="w-14 h-14 bg-purple-100 rounded-2xl flex items-center justify-center text-2xl">✅</div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('landing.impact_oversells') }}</h3>
                    <p class="text-gray-600">{{ __('landing.impact_oversells_desc') }}</p>
                </div>
                
                <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all scroll-reveal-card hover-lift">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-5xl font-bold text-orange-600">-25%</div>
                        <div class="w-14 h-14 bg-orange-100 rounded-2xl flex items-center justify-center text-2xl">📦</div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('landing.impact_dead_stock') }}</h3>
                    <p class="text-gray-600">{{ __('landing.impact_dead_stock_desc') }}</p>
                </div>
                
                <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all scroll-reveal-card hover-lift">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-5xl font-bold text-indigo-600">24/7</div>
                        <div class="w-14 h-14 bg-indigo-100 rounded-2xl flex items-center justify-center text-2xl">🔔</div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('landing.impact_monitoring') }}</h3>
                    <p class="text-gray-600">{{ __('landing.impact_monitoring_desc') }}</p>
                </div>
                
                <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all scroll-reveal-card hover-lift">
                    <div class="flex items-center justify-between mb-4">
                        <div class="text-5xl font-bold text-teal-600">80%</div>
                        <div class="w-14 h-14 bg-teal-100 rounded-2xl flex items-center justify-center text-2xl">⚡</div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('landing.impact_efficiency') }}</h3>
                    <p class="text-gray-600">{{ __('landing.impact_efficiency_desc') }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Automation Showcase Section - NEW -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 scroll-reveal">
                <div class="text-sm font-semibold text-blue-600 uppercase tracking-wide mb-2">{{ __('landing.nav_features') }}</div>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">{{ __('landing.automation_title') }}</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">{{ __('landing.automation_subtitle') }}</p>
            </div>
            
            <div class="max-w-4xl mx-auto">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-3xl p-8 md:p-12 scroll-reveal">
                    <div class="space-y-6">
                        <div class="flex items-start gap-6">
                            <div class="flex-shrink-0 w-24 text-right">
                                <div class="text-sm font-semibold text-blue-600">{!! __('landing.automation_10min') !!}</div>
                            </div>
                            <div class="flex-shrink-0 w-3 h-3 bg-blue-600 rounded-full mt-2"></div>
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-900 mb-2">{{ __('landing.automation_10min_title') }}</h3>
                                <p class="text-gray-600">{{ __('landing.automation_10min_desc') }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-6">
                            <div class="flex-shrink-0 w-24 text-right">
                                <div class="text-sm font-semibold text-green-600">{!! __('landing.automation_hour') !!}</div>
                            </div>
                            <div class="flex-shrink-0 w-3 h-3 bg-green-600 rounded-full mt-2"></div>
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-900 mb-2">{{ __('landing.automation_hour_title') }}</h3>
                                <p class="text-gray-600">{{ __('landing.automation_hour_desc') }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-6">
                            <div class="flex-shrink-0 w-24 text-right">
                                <div class="text-sm font-semibold text-orange-600">{!! __('landing.automation_daily') !!}</div>
                            </div>
                            <div class="flex-shrink-0 w-3 h-3 bg-orange-600 rounded-full mt-2"></div>
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-900 mb-2">{{ __('landing.automation_daily_title') }}</h3>
                                <p class="text-gray-600">{{ __('landing.automation_daily_desc') }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-6">
                            <div class="flex-shrink-0 w-24 text-right">
                                <div class="text-sm font-semibold text-purple-600">{!! __('landing.automation_weekly') !!}</div>
                            </div>
                            <div class="flex-shrink-0 w-3 h-3 bg-purple-600 rounded-full mt-2"></div>
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-900 mb-2">{{ __('landing.automation_weekly_title') }}</h3>
                                <p class="text-gray-600">{{ __('landing.automation_weekly_desc') }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-10 p-6 bg-white rounded-2xl border-2 border-blue-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="font-bold text-gray-900 mb-1">{{ __('landing.automation_tech') }}</h4>
                                <p class="text-sm text-gray-600">{{ __('landing.automation_tech_desc') }}</p>
                            </div>
                            <div class="text-4xl">⚙️</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Integrations -->
    <section id="integrations" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <div class="text-sm font-semibold text-blue-600 uppercase tracking-wide mb-2">Интеграции</div>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Подключённые маркетплейсы</h2>
                <p class="text-lg text-gray-600">Работаем с ведущими площадками</p>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="bg-white rounded-2xl p-8 text-center border border-gray-100 hover:shadow-xl transition">
                    <div class="w-20 h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-3xl font-bold shadow-lg">U</div>
                    <div class="font-bold text-gray-900 text-lg">Uzum Market</div>
                    <div class="text-sm text-green-600 font-medium mt-2">● Активно</div>
                </div>
                <div class="bg-white rounded-2xl p-8 text-center border border-gray-100 hover:shadow-xl transition">
                    <div class="w-20 h-20 bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-3xl font-bold shadow-lg">WB</div>
                    <div class="font-bold text-gray-900 text-lg">Wildberries</div>
                    <div class="text-sm text-green-600 font-medium mt-2">● Активно</div>
                </div>
                <div class="bg-white rounded-2xl p-8 text-center border border-gray-100 hover:shadow-xl transition">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-3xl font-bold shadow-lg">O</div>
                    <div class="font-bold text-gray-900 text-lg">Ozon</div>
                    <div class="text-sm text-green-600 font-medium mt-2">● Активно</div>
                </div>
                <div class="bg-white rounded-2xl p-8 text-center border border-gray-100 hover:shadow-xl transition">
                    <div class="w-20 h-20 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-3xl font-bold shadow-lg">Я</div>
                    <div class="font-bold text-gray-900 text-lg">Яндекс Маркет</div>
                    <div class="text-sm text-green-600 font-medium mt-2">● Активно</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section id="testimonials" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <div class="text-sm font-semibold text-blue-600 uppercase tracking-wide mb-2">Отзывы</div>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Что говорят наши клиенты</h2>
            </div>
            
            <div class="grid md:grid-cols-3 gap-8">
                <div class="bg-gray-50 rounded-2xl p-8">
                    <div class="flex gap-1 mb-4">
                        <span class="text-yellow-400 text-xl">★★★★★</span>
                    </div>
                    <p class="text-gray-700 mb-6">"Перешли на SellerMind полгода назад. Время на управление остатками сократилось в 5 раз. Рекомендую всем, кто работает с несколькими площадками."</p>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center font-bold text-blue-600">АМ</div>
                        <div>
                            <div class="font-bold text-gray-900">Алексей М.</div>
                            <div class="text-sm text-gray-600">Селлер, 3000+ SKU</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-2xl p-8">
                    <div class="flex gap-1 mb-4">
                        <span class="text-yellow-400 text-xl">★★★★★</span>
                    </div>
                    <p class="text-gray-700 mb-6">"AI-помощник — это находка! Генерирует описания за секунды, отвечает на отзывы профессионально. Сэкономили на контент-менеджере."</p>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center font-bold text-green-600">ДК</div>
                        <div>
                            <div class="font-bold text-gray-900">Дарья К.</div>
                            <div class="text-sm text-gray-600">Владелец магазина</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50 rounded-2xl p-8">
                    <div class="flex gap-1 mb-4">
                        <span class="text-yellow-400 text-xl">★★★★★</span>
                    </div>
                    <p class="text-gray-700 mb-6">"Складской учёт — супер! Раньше вели в Excel, теперь всё автоматически. Знаем остатки в реальном времени на всех складах."</p>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center font-bold text-purple-600">СИ</div>
                        <div>
                            <div class="font-bold text-gray-900">Сергей И.</div>
                            <div class="text-sm text-gray-600">Директор, Retail Group</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section id="pricing" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <div class="text-sm font-semibold text-blue-600 uppercase tracking-wide mb-2">Тарифы</div>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Прозрачное ценообразование</h2>
                <p class="text-lg text-gray-600">Без скрытых платежей. Отмена в любой момент.</p>
            </div>
            
            <div class="grid md:grid-cols-4 gap-8 max-w-6xl mx-auto">
                @foreach($plans as $plan)
                @if($plan->is_popular)
                <div class="bg-blue-600 rounded-2xl p-8 relative shadow-xl shadow-blue-600/30">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-orange-400 text-orange-900 text-xs font-bold rounded-full">
                        Популярный
                    </div>
                    <div class="text-white/80 text-sm font-medium mb-2">{{ $plan->name }}</div>
                    <div class="text-4xl font-bold text-white mb-1">
                        @if($plan->price == 0)
                            Бесплатно
                        @elseif($plan->slug === 'enterprise')
                            от {{ number_format($plan->price, 0, ',', ' ') }}
                        @else
                            {{ number_format($plan->price, 0, ',', ' ') }}<span class="text-xl font-normal">/мес</span>
                        @endif
                    </div>
                    <div class="text-white/80 mb-6">@if($plan->price == 0) навсегда @else сум @endif</div>
                    <ul class="space-y-3 mb-8 text-white text-sm">
                        <li class="flex items-center"><span class="mr-2">✓</span> 
                            @if($plan->max_marketplace_accounts == -1) Безлимит аккаунтов @else {{ $plan->max_marketplace_accounts }} маркетплейса @endif
                        </li>
                        <li class="flex items-center"><span class="mr-2">✓</span>
                            @if($plan->max_products == -1) Безлимит товаров @else {{ number_format($plan->max_products) }} товаров @endif
                        </li>
                        <li class="flex items-center"><span class="mr-2">✓</span>
                            @if($plan->max_orders_per_month == -1) Безлимит заказов @else {{ number_format($plan->max_orders_per_month) }} заказов/мес @endif
                        </li>
                        @if($plan->has_analytics)<li class="flex items-center"><span class="mr-2">✓</span> Аналитика</li>@endif
                        @if($plan->has_auto_pricing)<li class="flex items-center"><span class="mr-2">✓</span> Автоценообразование</li>@endif
                        @if($plan->has_api_access)<li class="flex items-center"><span class="mr-2">✓</span> API доступ</li>@endif
                        @if($plan->has_priority_support)<li class="flex items-center"><span class="mr-2">✓</span> Приоритетная поддержка</li>@endif
                    </ul>
                    <a href="/register" class="block w-full py-3 text-center bg-white text-blue-600 font-bold rounded-xl hover:bg-blue-50 transition">
                        Выбрать план
                    </a>
                </div>
                @else
                <div class="bg-white rounded-2xl p-8 border border-gray-200">
                    <div class="text-gray-500 text-sm font-medium mb-2">{{ $plan->name }}</div>
                    <div class="text-4xl font-bold text-gray-900 mb-1">
                        @if($plan->price == 0)
                            Бесплатно
                        @elseif($plan->slug === 'enterprise')
                            от {{ number_format($plan->price, 0, ',', ' ') }}
                        @else
                            {{ number_format($plan->price, 0, ',', ' ') }}<span class="text-xl font-normal">/мес</span>
                        @endif
                    </div>
                    <div class="text-gray-500 mb-6">@if($plan->price == 0) навсегда @else сум @endif</div>
                    <ul class="space-y-3 mb-8 text-gray-600 text-sm">
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span> 
                            @if($plan->max_marketplace_accounts == -1) Безлимит аккаунтов @else {{ $plan->max_marketplace_accounts }} {{ $plan->max_marketplace_accounts == 1 ? 'маркетплейс' : 'маркетплейса' }} @endif
                        </li>
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span>
                            @if($plan->max_products == -1) Безлимит товаров @else {{ number_format($plan->max_products) }} товаров @endif
                        </li>
                        <li class="flex items-center"><span class="text-green-500 mr-2">✓</span>
                            @if($plan->max_orders_per_month == -1) Безлимит заказов @else {{ number_format($plan->max_orders_per_month) }} заказов/мес @endif
                        </li>
                        @if($plan->has_analytics)<li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Аналитика</li>@endif
                        @if($plan->has_auto_pricing)<li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Автоценообразование</li>@endif
                        @if($plan->has_api_access)<li class="flex items-center"><span class="text-green-500 mr-2">✓</span> API доступ</li>@endif
                        @if($plan->has_priority_support)<li class="flex items-center"><span class="text-green-500 mr-2">✓</span> Приоритетная поддержка</li>@endif
                    </ul>
                    <a href="{{ $plan->slug === 'enterprise' ? '#' : '/register' }}" class="block w-full py-3 text-center border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition font-medium">
                        {{ $plan->slug === 'enterprise' ? 'Связаться' : 'Выбрать план' }}
                    </a>
                </div>
                @endif
                @endforeach
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section id="faq" class="py-20 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <div class="text-sm font-semibold text-blue-600 uppercase tracking-wide mb-2">FAQ</div>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">Частые вопросы</h2>
            </div>
            
            <div class="space-y-4">
                <div class="border border-gray-200 rounded-xl">
                    <button @click="faqOpen = faqOpen === 1 ? null : 1" class="w-full px-6 py-4 text-left flex items-center justify-between">
                        <span class="font-medium text-gray-900">Как происходит синхронизация остатков?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="faqOpen === 1 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="faqOpen === 1" x-cloak class="px-6 pb-4 text-gray-600">
                        Система автоматически синхронизирует остатки между вашим складом и всеми подключёнными маркетплейсами каждые 5-15 минут. При продаже на любой площадке остатки мгновенно обновляются везде.
                    </div>
                </div>
                
                <div class="border border-gray-200 rounded-xl">
                    <button @click="faqOpen = faqOpen === 2 ? null : 2" class="w-full px-6 py-4 text-left flex items-center justify-between">
                        <span class="font-medium text-gray-900">Какие маркетплейсы поддерживаются?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="faqOpen === 2 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="faqOpen === 2" x-cloak class="px-6 pb-4 text-gray-600">
                        Сейчас полностью поддерживаются Uzum Market и Wildberries. Ozon и Яндекс Маркет находятся в разработке и будут доступны в ближайшее время.
                    </div>
                </div>
                
                <div class="border border-gray-200 rounded-xl">
                    <button @click="faqOpen = faqOpen === 3 ? null : 3" class="w-full px-6 py-4 text-left flex items-center justify-between">
                        <span class="font-medium text-gray-900">Можно ли попробовать бесплатно?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="faqOpen === 3 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="faqOpen === 3" x-cloak class="px-6 pb-4 text-gray-600">
                        Да! Тариф "Старт" бесплатен навсегда. Вы можете подключить 1 маркетплейс и до 100 товаров без ограничения по времени. Для расширения возможностей переходите на платный тариф.
                    </div>
                </div>
                
                <div class="border border-gray-200 rounded-xl">
                    <button @click="faqOpen = faqOpen === 4 ? null : 4" class="w-full px-6 py-4 text-left flex items-center justify-between">
                        <span class="font-medium text-gray-900">Как работает AI-помощник?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="faqOpen === 4 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="faqOpen === 4" x-cloak class="px-6 pb-4 text-gray-600">
                        AI-помощник использует современные языковые модели для генерации описаний товаров, ответов на отзывы покупателей и создания промо-материалов. Просто опишите задачу, и ИИ выполнит её за секунды.
                    </div>
                </div>
                
                <div class="border border-gray-200 rounded-xl">
                    <button @click="faqOpen = faqOpen === 5 ? null : 5" class="w-full px-6 py-4 text-left flex items-center justify-between">
                        <span class="font-medium text-gray-900">Безопасно ли хранение данных?</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform" :class="faqOpen === 5 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="faqOpen === 5" x-cloak class="px-6 pb-4 text-gray-600">
                        Да, безопасность — наш приоритет. Все данные шифруются, сервера находятся в защищённых дата-центрах, регулярно создаются резервные копии. Мы не передаём данные третьим лицам.
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Comparison Section - NEW -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 scroll-reveal">
                <div class="text-sm font-semibold text-blue-600 uppercase tracking-wide mb-2">Сравнение</div>
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">{{ __('landing.comparison_title') }}</h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">{{ __('landing.comparison_subtitle') }}</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b-2 border-gray-200">
                            <th class="py-4 px-4 text-left text-gray-900 font-bold">Функция</th>
                            <th class="py-4 px-4 text-center">
                                <div class="text-blue-600 font-bold text-lg mb-1">SellerMind</div>
                                <div class="text-xs text-gray-500">Наша система</div>
                            </th>
                            <th class="py-4 px-4 text-center">
                                <div class="text-gray-700 font-semibold mb-1">Компания 1</div>
                                <div class="text-xs text-gray-500">Конкурент</div>
                            </th>
                            <th class="py-4 px-4 text-center">
                                <div class="text-gray-700 font-semibold mb-1">Компания 2</div>
                                <div class="text-xs text-gray-500">Конкурент</div>
                            </th>
                            <th class="py-4 px-4 text-center">
                                <div class="text-gray-700 font-semibold mb-1">Компания 3</div>
                                <div class="text-xs text-gray-500">Конкурент</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-4 px-4 font-medium text-gray-900">Синхронизация остатков</td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                        </tr>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-4 px-4 font-medium text-gray-900">AI-помощник</td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                        </tr>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-4 px-4 font-medium text-gray-900">Складской учёт (WMS)</td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                        </tr>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-4 px-4 font-medium text-gray-900">Автоматические промо-акции</td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                        </tr>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-4 px-4 font-medium text-gray-900">Умное ценообразование</td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                        </tr>
                        <tr class="border-b border-gray-100 hover:bg-gray-50 bg-blue-50">
                            <td class="py-4 px-4 font-medium text-gray-900">
                                <div class="flex items-center gap-2">
                                    <span>Интеграция с 4+ маркетплейсами</span>
                                    <span class="text-xs bg-blue-600 text-white px-2 py-1 rounded-full">ТОЛЬКО У НАС</span>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                        </tr>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-4 px-4 font-medium text-gray-900">Аналитика и дашборды</td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                        </tr>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-4 px-4 font-medium text-gray-900">Telegram уведомления</td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                        </tr>
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="py-4 px-4 font-medium text-gray-900">Массовые операции</td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-green-600">✓</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-3xl text-gray-300">✗</span></td>
                        </tr>
                        <tr class="bg-blue-50">
                            <td class="py-4 px-4 font-bold text-gray-900">Всего функций</td>
                            <td class="py-4 px-4 text-center"><span class="text-2xl font-bold text-blue-600">9/9</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-2xl font-bold text-gray-600">3/9</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-2xl font-bold text-gray-600">4/9</span></td>
                            <td class="py-4 px-4 text-center"><span class="text-2xl font-bold text-gray-600">2/9</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-12 text-center">
                <a href="/register" class="inline-flex items-center px-8 py-4 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition text-lg shadow-lg shadow-blue-600/30">
                    {{ __('landing.comparison_cta') }}
                </a>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-20 bg-blue-600">
        <div class="max-w-4xl mx-auto px-4 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold mb-6" style="color: white !important;">Готовы масштабировать бизнес?</h2>
            <p class="text-xl mb-8" style="color: white !important;">Присоединяйтесь к 500+ компаниям на SellerMind</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/register" class="px-10 py-4 bg-white font-bold rounded-xl hover:bg-blue-50 transition text-lg shadow-xl" style="color: #2563eb !important;">
                    Начать бесплатно
                </a>
                <a href="#" class="px-10 py-4 border-2 border-white font-bold rounded-xl hover:bg-blue-700 transition text-lg" style="color: white !important;">
                    Запросить демо
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center space-x-2 mb-4">
                        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <span class="text-white font-bold">SellerMind</span>
                    </div>
                    <p class="text-sm text-gray-500">Платформа для управления продажами на маркетплейсах</p>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Продукт</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#features" class="text-gray-300 hover:text-white transition">Возможности</a></li>
                        <li><a href="#pricing" class="text-gray-300 hover:text-white transition">Тарифы</a></li>
                        <li><a href="#integrations" class="text-gray-300 hover:text-white transition">Интеграции</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Ресурсы</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="text-gray-300 hover:text-white transition">Документация</a></li>
                        <li><a href="#faq" class="text-gray-300 hover:text-white transition">FAQ</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition">Блог</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-semibold mb-4">Контакты</h4>
                    <ul class="space-y-2 text-sm text-gray-300">
                        <li>info@sellermind.uz</li>
                        <li>+998 90 123 45 67</li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-sm text-gray-400">
                © 2025 SellerMind. Все права защищены.
            </div>
        </div>
    </footer>

<!-- Section Indicators -->
<div class="section-indicators">
    <div class="section-indicator" data-section="hero" title="Главная"></div>
    <div class="section-indicator" data-section="features" title="Возможности"></div>
    <div class="section-indicator" data-section="impact" title="Результаты"></div>
    <div class="section-indicator" data-section="automation" title="Автоматизация"></div>
    <div class="section-indicator" data-section="integrations" title="Интеграции"></div>
    <div class="section-indicator" data-section="comparison" title="Сравнение"></div>
    <div class="section-indicator" data-section="pricing" title="Тарифы"></div>
    <div class="section-indicator" data-section="faq" title="FAQ"></div>
</div>

<script>
// Intersection Observer for scroll reveal animations - Optimized
document.addEventListener('DOMContentLoaded', function() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
            }
        });
    }, {
        threshold: 0.15,
        rootMargin: '0px 0px -30px 0px'
    });
    
    // Observe all elements with scroll-reveal classes
    document.querySelectorAll('.scroll-reveal, .scroll-reveal-card').forEach(el => {
        observer.observe(el);
    });
    
    // Section Indicators - Optimized
    const sections = {
        'hero': document.querySelector('section:first-of-type'),
        'features': document.querySelector('#features'),
        'impact': document.querySelectorAll('section')[5],
        'automation': document.querySelectorAll('section')[6],
        'integrations': document.querySelector('#integrations'),
        'comparison': document.querySelectorAll('section')[9],
        'pricing': document.querySelector('#pricing'),
        'faq': document.querySelector('#faq')
    };
    
    const indicators = document.querySelectorAll('.section-indicator');
    
    // Click handler for indicators
    indicators.forEach(indicator => {
        indicator.addEventListener('click', () => {
            const sectionName = indicator.dataset.section;
            const section = sections[sectionName];
            if (section) {
                section.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
    
    // Debounce для производительности
    let ticking = false;
    
    // Update active indicator on scroll - Faster response
    const sectionObserver = new IntersectionObserver((entries) => {
        if (!ticking) {
            window.requestAnimationFrame(() => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && entry.intersectionRatio > 0.6) {
                        for (const [name, section] of Object.entries(sections)) {
                            if (section === entry.target) {
                                indicators.forEach(ind => ind.classList.remove('active'));
                                const activeIndicator = document.querySelector(`.section-indicator[data-section="${name}"]`);
                                if (activeIndicator) {
                                    activeIndicator.classList.add('active');
                                }
                                break;
                            }
                        }
                    }
                });
                ticking = false;
            });
            ticking = true;
        }
    }, {
        threshold: [0.3, 0.6, 0.9],
        rootMargin: '-10% 0px -10% 0px'
    });
    
    // Observe all sections
    Object.values(sections).forEach(section => {
        if (section) sectionObserver.observe(section);
    });
});
</script>

</body>
</html>
