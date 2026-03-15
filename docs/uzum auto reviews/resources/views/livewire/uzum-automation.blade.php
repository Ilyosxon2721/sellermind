<div class="max-w-4xl mx-auto space-y-6">
    {{-- Заголовок --}}
    <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Автоматизация Uzum Market</h2>
        <button wire:click="loadStats" class="text-sm text-blue-600 hover:text-blue-800">
            ↻ Обновить
        </button>
    </div>

    {{-- Карточки статистики --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border p-5">
            <div class="text-sm text-gray-500">Новых заказов</div>
            <div class="text-3xl font-bold text-orange-600 mt-1">{{ $pendingOrdersCount }}</div>
            <div class="text-xs text-gray-400 mt-1">Ждут подтверждения</div>
        </div>
        <div class="bg-white rounded-xl border p-5">
            <div class="text-sm text-gray-500">Подтверждено сегодня</div>
            <div class="text-3xl font-bold text-green-600 mt-1">{{ $todayConfirmed }}</div>
            <div class="text-xs text-gray-400 mt-1">Авто-подтверждение</div>
        </div>
        <div class="bg-white rounded-xl border p-5">
            <div class="text-sm text-gray-500">Ответов на отзывы</div>
            <div class="text-3xl font-bold text-blue-600 mt-1">{{ $todayReplied }}</div>
            <div class="text-xs text-gray-400 mt-1">ИИ авто-ответ сегодня</div>
        </div>
    </div>

    {{-- Табы --}}
    <div class="border-b">
        <nav class="flex space-x-6">
            @foreach (['orders' => '📦 Заказы', 'reviews' => '💬 Отзывы', 'logs' => '📋 Логи'] as $tab => $label)
                <button
                    wire:click="$set('activeTab', '{{ $tab }}')"
                    class="py-3 border-b-2 text-sm font-medium transition-colors
                        {{ $activeTab === $tab
                            ? 'border-blue-600 text-blue-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ═══ Таб: Заказы ═══ --}}
    @if ($activeTab === 'orders')
        <div class="bg-white rounded-xl border p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold">Авто-подтверждение заказов</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Автоматически переводит новые FBS/DBS заказы из «Новые» в «Сборку» каждые 15 минут
                    </p>
                </div>
                <button
                    wire:click="toggleAutoConfirm"
                    class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out
                        {{ $autoConfirmEnabled ? 'bg-green-500' : 'bg-gray-300' }}"
                >
                    <span class="inline-block h-6 w-6 transform rounded-full bg-white shadow transition duration-200
                        {{ $autoConfirmEnabled ? 'translate-x-5' : 'translate-x-0' }}">
                    </span>
                </button>
            </div>

            @if ($autoConfirmEnabled)
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-green-700">
                        <span class="text-lg">✅</span>
                        <span class="text-sm font-medium">Активно — проверка каждые 15 минут</span>
                    </div>
                </div>
            @endif

            <button
                wire:click="runConfirmNow"
                wire:loading.attr="disabled"
                class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="runConfirmNow">▶ Запустить сейчас</span>
                <span wire:loading wire:target="runConfirmNow">⏳ Запуск...</span>
            </button>
        </div>
    @endif

    {{-- ═══ Таб: Отзывы ═══ --}}
    @if ($activeTab === 'reviews')
        <div class="bg-white rounded-xl border p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold">ИИ авто-ответ на отзывы</h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Автоматически генерирует и отправляет ответы на отзывы покупателей через Claude AI
                    </p>
                </div>
                <button
                    wire:click="toggleAutoReply"
                    class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out
                        {{ $autoReplyEnabled ? 'bg-green-500' : 'bg-gray-300' }}"
                >
                    <span class="inline-block h-6 w-6 transform rounded-full bg-white shadow transition duration-200
                        {{ $autoReplyEnabled ? 'translate-x-5' : 'translate-x-0' }}">
                    </span>
                </button>
            </div>

            @if ($autoReplyEnabled)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-blue-700">
                        <span class="text-lg">🤖</span>
                        <span class="text-sm font-medium">ИИ активен — проверка каждые 30 минут</span>
                    </div>
                </div>
            @endif

            {{-- Тон ответов --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Тон ответов</label>
                <div class="flex gap-3">
                    @foreach (['friendly' => '😊 Дружелюбный', 'professional' => '👔 Деловой', 'casual' => '🤙 Неформальный'] as $value => $label)
                        <button
                            wire:click="$set('reviewTone', '{{ $value }}')"
                            @if ($reviewTone === $value) wire:click.prevent @endif
                            class="px-4 py-2 rounded-lg text-sm border transition-colors
                                {{ $reviewTone === $value
                                    ? 'bg-blue-100 border-blue-400 text-blue-700'
                                    : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                @if ($reviewTone !== ($this->getCurrentShop()?->review_tone ?? 'friendly'))
                    <button wire:click="updateTone" class="mt-2 text-sm text-blue-600 hover:underline">
                        Сохранить тон
                    </button>
                @endif
            </div>

            <div class="flex gap-3">
                <button
                    wire:click="runReplyNow"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="runReplyNow">▶ Ответить на отзывы сейчас</span>
                    <span wire:loading wire:target="runReplyNow">⏳ Запуск...</span>
                </button>
            </div>

            {{-- Подключение seller.uzum.uz --}}
            @if (!$sellerConnected)
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-5 space-y-4">
                    <div class="flex items-start gap-3">
                        <span class="text-2xl">🔑</span>
                        <div>
                            <h4 class="font-semibold text-amber-800">Подключи аккаунт seller.uzum.uz</h4>
                            <p class="text-sm text-amber-700 mt-1">
                                Для авто-ответа на отзывы нужна авторизация через seller panel.
                                Введи логин и пароль от seller.uzum.uz — данные зашифрованы и хранятся безопасно.
                            </p>
                        </div>
                    </div>

                    @if (!$showSellerLogin)
                        <button wire:click="$set('showSellerLogin', true)" class="px-4 py-2 bg-amber-600 text-white text-sm rounded-lg hover:bg-amber-700">
                            Подключить seller.uzum.uz
                        </button>
                    @else
                        <div class="space-y-3">
                            <input
                                type="email"
                                wire:model="sellerEmail"
                                placeholder="Email от seller.uzum.uz"
                                class="w-full border rounded-lg px-3 py-2 text-sm"
                            />
                            <input
                                type="password"
                                wire:model="sellerPassword"
                                placeholder="Пароль"
                                class="w-full border rounded-lg px-3 py-2 text-sm"
                            />
                            @if ($sellerLoginError)
                                <div class="text-red-600 text-sm">❌ {{ $sellerLoginError }}</div>
                            @endif
                            <div class="flex gap-2">
                                <button
                                    wire:click="connectSeller"
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 disabled:opacity-50"
                                >
                                    <span wire:loading.remove wire:target="connectSeller">Подключить</span>
                                    <span wire:loading wire:target="connectSeller">⏳ Авторизация...</span>
                                </button>
                                <button wire:click="$set('showSellerLogin', false)" class="px-3 py-2 text-sm text-gray-500">
                                    Отмена
                                </button>
                            </div>
                            <p class="text-xs text-gray-400">
                                🔒 Данные шифруются и используются только для получения токена. Авто-обновление токена каждый час.
                            </p>
                        </div>
                    @endif
                </div>
            @else
                <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-green-700">
                        <span class="text-lg">🔗</span>
                        <span class="text-sm font-medium">Seller.uzum.uz подключён — токен обновляется автоматически</span>
                    </div>
                    <button wire:click="disconnectSeller" class="text-xs text-red-500 hover:underline">
                        Отключить
                    </button>
                </div>
            @endif

            {{-- Информация об API --}}
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-start gap-2 text-green-700 text-sm">
                    <span class="text-lg mt-0.5">✅</span>
                    <div>
                        <span class="font-medium">API отзывов подключён.</span>
                        Эндпоинты: <code class="bg-green-100 px-1 rounded">POST /api/seller/product-reviews</code> (список)
                        и <code class="bg-green-100 px-1 rounded">POST /api/seller/product-reviews/reply/create</code> (ответ).
                        ИИ учитывает оценку, текст, плюсы, минусы и характеристики товара.
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══ Таб: Логи ═══ --}}
    @if ($activeTab === 'logs')
        <div class="space-y-6">
            {{-- Логи подтверждений --}}
            <div class="bg-white rounded-xl border p-6">
                <h3 class="text-lg font-semibold mb-4">Последние подтверждения</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="pb-2">ID заказа</th>
                                <th class="pb-2">Статус</th>
                                <th class="pb-2">Ошибка</th>
                                <th class="pb-2">Дата</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->confirmLogs as $log)
                                <tr class="border-b last:border-0">
                                    <td class="py-2 font-mono">{{ $log->uzum_order_id }}</td>
                                    <td class="py-2">
                                        @if ($log->status === 'confirmed')
                                            <span class="text-green-600">✅ Подтверждён</span>
                                        @else
                                            <span class="text-red-600">❌ Ошибка</span>
                                        @endif
                                    </td>
                                    <td class="py-2 text-gray-500 text-xs">{{ $log->error_code }}</td>
                                    <td class="py-2 text-gray-400">{{ $log->created_at?->format('d.m H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-gray-400">Нет данных</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Логи ответов на отзывы --}}
            <div class="bg-white rounded-xl border p-6">
                <h3 class="text-lg font-semibold mb-4">Последние ответы на отзывы</h3>
                <div class="space-y-3">
                    @forelse ($this->replyLogs as $log)
                        <div class="border rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-yellow-500">
                                        @for ($i = 0; $i < $log->rating; $i++) ⭐ @endfor
                                    </span>
                                    @if ($log->product_name)
                                        <span class="text-xs text-gray-400">{{ $log->product_name }}</span>
                                    @endif
                                </div>
                                <span class="text-xs {{ $log->status === 'sent' ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $log->status === 'sent' ? '✅ Отправлен' : '❌ Ошибка' }}
                                </span>
                            </div>
                            @if ($log->review_text)
                                <p class="text-sm text-gray-600 mb-2">
                                    <span class="text-gray-400">Отзыв:</span> {{ Str::limit($log->review_text, 150) }}
                                </p>
                            @endif
                            <p class="text-sm text-blue-700 bg-blue-50 rounded p-2">
                                <span class="text-blue-400">Ответ:</span> {{ $log->reply_text }}
                            </p>
                            <div class="text-xs text-gray-400 mt-1">{{ $log->created_at?->format('d.m.Y H:i') }}</div>
                        </div>
                    @empty
                        <div class="text-center text-gray-400 py-4">Нет ответов</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    {{-- API Token --}}
    <div class="bg-white rounded-xl border p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-medium text-gray-700">API Токен</h3>
                <p class="text-xs text-gray-400">Токен из seller.uzum.uz → Настройки → OpenAPI</p>
            </div>
            @if (!$showTokenInput)
                <button wire:click="$set('showTokenInput', true)" class="text-sm text-blue-600 hover:underline">
                    Изменить токен
                </button>
            @endif
        </div>
        @if ($showTokenInput)
            <div class="flex gap-2 mt-3">
                <input
                    type="password"
                    wire:model="apiToken"
                    placeholder="Вставь API токен..."
                    class="flex-1 border rounded-lg px-3 py-2 text-sm"
                />
                <button wire:click="saveToken" class="px-4 py-2 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700">
                    Сохранить
                </button>
                <button wire:click="$set('showTokenInput', false)" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">
                    ✕
                </button>
            </div>
        @endif
    </div>
</div>
