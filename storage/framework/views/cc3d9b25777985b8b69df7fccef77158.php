<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Регистрация — SellerMind</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <style>* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }</style>
</head>
<body class="antialiased bg-gray-50 min-h-screen"
      x-data="{
          name: '',
          email: '',
          password: '',
          password_confirmation: '',
          error: '',
          loading: false,
          showPassword: false,
          acceptTerms: false,
          get passwordStrength() {
              if (!this.password) return 0;
              let s = 0;
              if (this.password.length >= 8) s++;
              if (/[A-Z]/.test(this.password)) s++;
              if (/[0-9]/.test(this.password)) s++;
              if (/[^A-Za-z0-9]/.test(this.password)) s++;
              return s;
          },
          async register() {
              if (!this.acceptTerms) { this.error = 'Примите условия использования'; return; }
              if (this.password !== this.password_confirmation) { this.error = 'Пароли не совпадают'; return; }
              this.loading = true; this.error = '';
              try {
                  await $store.auth.register({ name: this.name, email: this.email, password: this.password, password_confirmation: this.password_confirmation });
                  window.location.href = '/home';
              } catch (e) { this.error = e.response?.data?.message || 'Ошибка регистрации'; }
              this.loading = false;
          }
      }">
    
    <div class="min-h-screen flex browser-only">
        <!-- Left Side - Branding -->
        <div class="hidden lg:flex lg:w-1/2 bg-gray-900 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900"></div>
            
            <!-- Decorative elements -->
            <div class="absolute top-20 left-20 w-72 h-72 bg-green-600/20 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-20 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
            
            <div class="relative z-10 flex flex-col justify-center px-12 xl:px-20">
                <!-- Logo -->
                <div class="mb-12">
                    <div class="flex items-center space-x-3 mb-8">
                        <div class="w-12 h-12 bg-blue-600 rounded-xl flex items-center justify-center">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <span class="text-2xl font-bold" style="color: white;">SellerMind</span>
                    </div>
                    <h1 class="text-4xl font-bold mb-4" style="color: white;">Присоединяйтесь к нам</h1>
                    <p class="text-lg" style="color: #9ca3af;">
                        500+ компаний уже управляют продажами на маркетплейсах с нашей платформой
                    </p>
                </div>
                
                <!-- Benefits -->
                <div class="space-y-5">
                    <div class="flex items-center space-x-4">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span style="color: white;">14 дней бесплатного периода</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span style="color: white;">Без банковской карты</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <span style="color: white;">Подключение за 5 минут</span>
                    </div>
                </div>
                
                <!-- Testimonial -->
                <div class="mt-12 p-6 bg-white/5 rounded-2xl border border-white/10">
                    <p class="italic mb-4" style="color: #e5e7eb;">"Перешли на SellerMind полгода назад. Время на управление остатками сократилось в 5 раз."</p>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center font-bold" style="color: white;">АМ</div>
                        <div class="ml-4">
                            <div class="font-medium" style="color: white;">Алексей М.</div>
                            <div style="color: #9ca3af;">Селлер, 3000+ SKU</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Side - Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12 bg-white">
            <div class="w-full max-w-md">
                <!-- Mobile Logo -->
                <div class="lg:hidden text-center mb-8">
                    <div class="inline-flex items-center justify-center w-14 h-14 bg-blue-600 rounded-xl mb-4">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">SellerMind</h1>
                </div>
                
                <!-- Form Header -->
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Создать аккаунт</h2>
                    <p class="text-gray-600">Начните бесплатный период</p>
                </div>
                
                <!-- Error -->
                <div x-show="error" x-cloak class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <div class="flex items-center text-red-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span x-text="error"></span>
                    </div>
                </div>
                
                <!-- Form -->
                <form @submit.prevent="register" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Имя</label>
                        <input type="text" x-model="name" required autocomplete="name"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition text-gray-900"
                               placeholder="Ваше имя">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" x-model="email" required autocomplete="email"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition text-gray-900"
                               placeholder="you@example.com">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Пароль</label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" x-model="password" required minlength="8"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition pr-12 text-gray-900"
                                   placeholder="Минимум 8 символов">
                            <button type="button" @click="showPassword = !showPassword" 
                                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        <!-- Password strength -->
                        <div class="mt-2 flex gap-1">
                            <div class="h-1 flex-1 rounded" :class="passwordStrength >= 1 ? 'bg-red-500' : 'bg-gray-200'"></div>
                            <div class="h-1 flex-1 rounded" :class="passwordStrength >= 2 ? 'bg-yellow-500' : 'bg-gray-200'"></div>
                            <div class="h-1 flex-1 rounded" :class="passwordStrength >= 3 ? 'bg-green-400' : 'bg-gray-200'"></div>
                            <div class="h-1 flex-1 rounded" :class="passwordStrength >= 4 ? 'bg-green-600' : 'bg-gray-200'"></div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Подтвердите пароль</label>
                        <input type="password" x-model="password_confirmation" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition text-gray-900"
                               placeholder="Повторите пароль">
                    </div>
                    
                    <div class="flex items-start">
                        <input type="checkbox" x-model="acceptTerms" 
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mt-1">
                        <span class="ml-2 text-sm text-gray-600">
                            Я принимаю <a href="#" class="text-blue-600 hover:underline">условия</a> и <a href="#" class="text-blue-600 hover:underline">политику</a>
                        </span>
                    </div>
                    
                    <button type="submit" :disabled="loading"
                            class="w-full py-3.5 bg-blue-600 font-semibold rounded-xl hover:bg-blue-700 disabled:opacity-50 transition shadow-lg shadow-blue-600/30"
                            style="color: white !important;">
                        <span x-show="!loading">Создать аккаунт</span>
                        <span x-show="loading" class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Регистрация...
                        </span>
                    </button>
                </form>
                
                <!-- Login Link -->
                <div class="mt-8 text-center">
                    <p class="text-gray-600">
                        Уже есть аккаунт? 
                        <a href="/login" class="text-blue-600 hover:text-blue-700 font-semibold">Войти</a>
                    </p>
                </div>
                
                <!-- Back to Home -->
                <div class="mt-6 text-center">
                    <a href="/" class="text-sm text-gray-500 hover:text-gray-700 inline-flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        На главную
                    </a>
                </div>
            </div>
        </div>
    </div>

    
    <div class="pwa-only min-h-screen bg-white flex flex-col"
         style="padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom); padding-left: env(safe-area-inset-left); padding-right: env(safe-area-inset-right);">

        <div class="flex-1 flex flex-col justify-center px-6 py-8 overflow-y-auto">
            <!-- Logo -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-blue-600 rounded-xl mb-3">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <h1 class="text-xl font-bold text-gray-900">Создать аккаунт</h1>
                <p class="text-gray-500 text-sm mt-1">Начните бесплатный период</p>
            </div>

            <!-- Error -->
            <div x-show="error" x-cloak class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl">
                <div class="flex items-center text-red-700 text-sm">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-text="error"></span>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="register" class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Имя</label>
                    <input type="text" x-model="name" required autocomplete="name"
                           class="native-input w-full"
                           placeholder="Ваше имя">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" x-model="email" required autocomplete="email"
                           class="native-input w-full"
                           placeholder="you@example.com">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" x-model="password" required minlength="8"
                               class="native-input w-full pr-12"
                               placeholder="Минимум 8 символов">
                        <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 p-1">
                            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                            </svg>
                        </button>
                    </div>
                    <!-- Password strength -->
                    <div class="mt-1.5 flex gap-1">
                        <div class="h-1 flex-1 rounded" :class="passwordStrength >= 1 ? 'bg-red-500' : 'bg-gray-200'"></div>
                        <div class="h-1 flex-1 rounded" :class="passwordStrength >= 2 ? 'bg-yellow-500' : 'bg-gray-200'"></div>
                        <div class="h-1 flex-1 rounded" :class="passwordStrength >= 3 ? 'bg-green-400' : 'bg-gray-200'"></div>
                        <div class="h-1 flex-1 rounded" :class="passwordStrength >= 4 ? 'bg-green-600' : 'bg-gray-200'"></div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Подтвердите пароль</label>
                    <input type="password" x-model="password_confirmation" required
                           class="native-input w-full"
                           placeholder="Повторите пароль">
                </div>

                <div class="flex items-start pt-1">
                    <input type="checkbox" x-model="acceptTerms"
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded mt-0.5">
                    <span class="ml-2 text-sm text-gray-600">
                        Я принимаю <a href="#" class="text-blue-600">условия</a> и <a href="#" class="text-blue-600">политику</a>
                    </span>
                </div>

                <button type="submit" :disabled="loading"
                        class="native-btn native-btn-primary w-full py-3.5 text-base mt-2">
                    <span x-show="!loading">Создать аккаунт</span>
                    <span x-show="loading" class="flex items-center justify-center">
                        <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Регистрация...
                    </span>
                </button>
            </form>

            <!-- Links -->
            <div class="mt-5 text-center">
                <p class="text-gray-600">
                    Уже есть аккаунт?
                    <a href="/login" class="text-blue-600 font-semibold">Войти</a>
                </p>
            </div>

            <div class="mt-3 text-center">
                <a href="/" class="text-sm text-gray-500 inline-flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    На главную
                </a>
            </div>
        </div>
    </div>
</body>
</html>
<?php /**PATH /Applications/MAMP/htdocs/sellermind-ai/resources/views/pages/register.blade.php ENDPATH**/ ?>