@extends('storefront.layouts.app')

@section('content')
@php
    $currency = $store->currency ?? 'сум';

    $statusLabels = [
        'new' => 'Новый',
        'confirmed' => 'Подтвержден',
        'processing' => 'В обработке',
        'shipped' => 'Отправлен',
        'delivered' => 'Доставлен',
        'cancelled' => 'Отменен',
    ];

    $statusColors = [
        'new' => 'bg-blue-50 text-blue-700 border-blue-200',
        'confirmed' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'processing' => 'bg-yellow-50 text-yellow-700 border-yellow-200',
        'shipped' => 'bg-purple-50 text-purple-700 border-purple-200',
        'delivered' => 'bg-green-50 text-green-700 border-green-200',
        'cancelled' => 'bg-red-50 text-red-700 border-red-200',
    ];

    $paymentLabels = [
        'pending' => 'Ожидает оплаты',
        'paid' => 'Оплачен',
        'failed' => 'Ошибка оплаты',
        'refunded' => 'Возврат',
    ];

    $timelineSteps = ['new', 'confirmed', 'processing', 'shipped', 'delivered'];
    $currentStepIndex = array_search($order->status, $timelineSteps);
    if ($currentStepIndex === false) $currentStepIndex = -1;
@endphp

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
    {{-- Успешное оформление --}}
    @if($order->status === 'new')
        <div class="text-center mb-14">
            <div class="w-16 h-16 mx-auto mb-5 rounded-full border-2 border-gray-900 flex items-center justify-center">
                <svg class="w-7 h-7 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-semibold mb-2">Заказ оформлен</h1>
            <p class="text-gray-400">Мы свяжемся с вами для подтверждения</p>
        </div>
    @else
        <h1 class="text-2xl sm:text-3xl font-semibold mb-10">
            Заказ {{ $order->order_number }}
        </h1>
    @endif

    <div class="space-y-8">
        {{-- Шапка --}}
        <div class="border border-gray-200 rounded-lg p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <p class="text-xs font-medium uppercase tracking-widest text-gray-400 mb-1">Номер заказа</p>
                    <p class="text-lg font-semibold">{{ $order->order_number }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium border {{ $statusColors[$order->status] ?? 'bg-gray-50 text-gray-700 border-gray-200' }}">
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                    @if($order->payment_status)
                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-medium border {{ $order->payment_status === 'paid' ? 'bg-green-50 text-green-700 border-green-200' : 'bg-gray-50 text-gray-700 border-gray-200' }}">
                            {{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 text-sm">
                <div>
                    <p class="text-gray-400 mb-0.5">Дата</p>
                    <p class="font-medium">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-gray-400 mb-0.5">Покупатель</p>
                    <p class="font-medium">{{ $order->customer_name }}</p>
                    <p class="text-gray-400 text-xs">{{ $order->customer_phone }}</p>
                </div>
                @if($order->deliveryMethod)
                    <div>
                        <p class="text-gray-400 mb-0.5">Доставка</p>
                        <p class="font-medium">{{ $order->deliveryMethod->name }}</p>
                        @if($order->delivery_address)
                            <p class="text-gray-400 text-xs">
                                {{ $order->delivery_city ? $order->delivery_city . ', ' : '' }}{{ $order->delivery_address }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Таймлайн --}}
        @if($order->status !== 'cancelled')
            <div class="border border-gray-200 rounded-lg p-6">
                <h2 class="text-xs font-medium uppercase tracking-widest text-gray-400 mb-8">Прогресс</h2>

                {{-- Горизонтальный таймлайн --}}
                <div class="hidden sm:block">
                    <div class="relative flex items-center justify-between">
                        {{-- Линия фоновая --}}
                        <div class="absolute top-3 left-3 right-3 h-px bg-gray-200"></div>
                        {{-- Линия прогресса --}}
                        @if($currentStepIndex > 0)
                            <div
                                class="absolute top-3 left-3 h-px bg-gray-900 transition-all duration-500"
                                style="width: calc({{ ($currentStepIndex / (count($timelineSteps) - 1)) * 100 }}% - 24px);"
                            ></div>
                        @endif

                        @foreach($timelineSteps as $stepIndex => $step)
                            <div class="relative z-10 flex flex-col items-center gap-2">
                                <div
                                    class="w-6 h-6 rounded-full flex items-center justify-center transition-colors"
                                    style="{{ $stepIndex <= $currentStepIndex ? 'background: #111827; color: white;' : 'background: white; border: 1px solid #e5e7eb; color: #9ca3af;' }}"
                                >
                                    @if($stepIndex < $currentStepIndex)
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @elseif($stepIndex === $currentStepIndex)
                                        <div class="w-2 h-2 rounded-full bg-white"></div>
                                    @endif
                                </div>
                                <span class="text-xs {{ $stepIndex <= $currentStepIndex ? 'text-gray-900 font-medium' : 'text-gray-400' }}">
                                    {{ $statusLabels[$step] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Вертикальный таймлайн (мобильный) --}}
                <div class="sm:hidden space-y-4">
                    @foreach($timelineSteps as $stepIndex => $step)
                        <div class="flex items-center gap-3">
                            <div
                                class="w-6 h-6 rounded-full flex items-center justify-center shrink-0"
                                style="{{ $stepIndex <= $currentStepIndex ? 'background: #111827; color: white;' : 'background: white; border: 1px solid #e5e7eb; color: #9ca3af;' }}"
                            >
                                @if($stepIndex < $currentStepIndex)
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @elseif($stepIndex === $currentStepIndex)
                                    <div class="w-2 h-2 rounded-full bg-white"></div>
                                @endif
                            </div>
                            <span class="text-sm {{ $stepIndex <= $currentStepIndex ? 'text-gray-900 font-medium' : 'text-gray-400' }}">
                                {{ $statusLabels[$step] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Товары --}}
        <div class="border border-gray-200 rounded-lg p-6">
            <h2 class="text-xs font-medium uppercase tracking-widest text-gray-400 mb-6">Товары</h2>

            <div class="space-y-5">
                @foreach($order->items as $item)
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded border border-gray-200 bg-gray-50 overflow-hidden shrink-0">
                            @if($item->product?->mainImage)
                                <img
                                    src="{{ $item->product->mainImage->url }}"
                                    alt="{{ $item->name }}"
                                    class="w-full h-full object-cover"
                                >
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $item->name }}</p>
                            @if($item->variant_name)
                                <p class="text-xs text-gray-400">{{ $item->variant_name }}</p>
                            @endif
                            <p class="text-xs text-gray-400 mt-0.5">{{ $item->quantity }} x {{ number_format($item->price, 0, '.', ' ') }} {{ $currency }}</p>
                        </div>
                        <span class="text-sm font-medium shrink-0">{{ number_format($item->total, 0, '.', ' ') }} {{ $currency }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Итого --}}
            <div class="mt-6 pt-5 border-t border-gray-100 space-y-2.5">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-400">Подитог</span>
                    <span class="font-medium">{{ number_format($order->subtotal, 0, '.', ' ') }} {{ $currency }}</span>
                </div>
                @if((float)$order->discount > 0)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-green-600">Скидка</span>
                        <span class="font-medium text-green-600">-{{ number_format($order->discount, 0, '.', ' ') }} {{ $currency }}</span>
                    </div>
                @endif
                @if((float)$order->delivery_price > 0)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-400">Доставка</span>
                        <span class="font-medium">{{ number_format($order->delivery_price, 0, '.', ' ') }} {{ $currency }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                    <span class="font-semibold">Итого</span>
                    <span class="text-lg font-semibold" style="color: var(--primary);">
                        {{ number_format($order->total, 0, '.', ' ') }} {{ $currency }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Кнопки --}}
        <div class="flex flex-col sm:flex-row gap-3">
            <a
                href="/store/{{ $store->slug }}"
                class="px-8 py-2.5 text-center text-sm font-medium bg-gray-900 text-white rounded hover:bg-gray-800 transition-colors"
            >
                На главную
            </a>
            <a
                href="/store/{{ $store->slug }}/catalog"
                class="px-8 py-2.5 text-center text-sm font-medium border border-gray-200 text-gray-700 rounded hover:border-gray-400 transition-colors"
            >
                Продолжить покупки
            </a>
        </div>
    </div>
</div>
@endsection
