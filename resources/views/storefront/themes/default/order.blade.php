@extends('storefront.layouts.app')

@section('page_title', 'Заказ ' . $order->order_number . ' — ' . $store->name)

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
        'new' => 'bg-blue-100 text-blue-700',
        'confirmed' => 'bg-indigo-100 text-indigo-700',
        'processing' => 'bg-yellow-100 text-yellow-700',
        'shipped' => 'bg-purple-100 text-purple-700',
        'delivered' => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-700',
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

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    {{-- Успешное оформление --}}
    @if($order->status === 'new')
        <div class="text-center mb-10">
            <div class="w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-5 bg-primary/10 animate-checkmark">
                <svg class="w-10 h-10 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold mb-2">Заказ оформлен!</h1>
            <p class="text-gray-500">Спасибо за заказ. Мы свяжемся с вами для подтверждения.</p>
        </div>
    @else
        <h1 class="text-2xl sm:text-3xl font-bold mb-8">
            Заказ {{ $order->order_number }}
        </h1>
    @endif

    {{-- Информация о заказе --}}
    <div class="space-y-6">
        {{-- Шапка заказа --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <p class="text-sm text-gray-500">Номер заказа</p>
                    <p class="text-lg font-bold">{{ $order->order_number }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex px-3 py-1.5 rounded-full text-xs font-semibold {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                    @if($order->payment_status)
                        <span class="inline-flex px-3 py-1.5 rounded-full text-xs font-semibold {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                            {{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div>
                    <p class="text-gray-500">Дата заказа</p>
                    <p class="font-medium mt-0.5">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Покупатель</p>
                    <p class="font-medium mt-0.5">{{ $order->customer_name }}</p>
                    <p class="text-gray-500 text-xs mt-0.5">{{ $order->customer_phone }}</p>
                </div>
                @if($order->deliveryMethod)
                    <div>
                        <p class="text-gray-500">Доставка</p>
                        <p class="font-medium mt-0.5">{{ $order->deliveryMethod->name }}</p>
                        @if($order->delivery_address)
                            <p class="text-gray-500 text-xs mt-0.5">
                                {{ $order->delivery_city ? $order->delivery_city . ', ' : '' }}{{ $order->delivery_address }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Таймлайн статуса --}}
        @if($order->status !== 'cancelled')
            <div class="bg-white rounded-2xl p-6 shadow-sm">
                <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-6">Статус заказа</h2>
                <div class="relative">
                    {{-- Линия --}}
                    <div class="absolute top-5 left-5 right-5 h-0.5 bg-gray-200 hidden sm:block">
                        @if($currentStepIndex > 0)
                            <div
                                class="absolute top-0 left-0 h-full transition-all duration-500"
                                style="background: var(--primary); width: {{ ($currentStepIndex / (count($timelineSteps) - 1)) * 100 }}%;"
                            ></div>
                        @endif
                    </div>

                    <div class="flex flex-col sm:flex-row sm:justify-between gap-4 sm:gap-0 relative">
                        @foreach($timelineSteps as $stepIndex => $step)
                            <div class="flex sm:flex-col items-center gap-3 sm:gap-2 sm:w-1/5">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 transition-colors duration-300 relative z-10"
                                    style="{{ $stepIndex <= $currentStepIndex ? 'background: var(--primary); color: white;' : 'background: #f3f4f6; color: #9ca3af;' }}"
                                >
                                    @if($stepIndex < $currentStepIndex)
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @elseif($stepIndex === $currentStepIndex)
                                        <div class="w-3 h-3 rounded-full bg-white"></div>
                                    @else
                                        <span class="text-xs font-medium">{{ $stepIndex + 1 }}</span>
                                    @endif
                                </div>
                                <span class="text-xs font-medium {{ $stepIndex <= $currentStepIndex ? 'text-gray-900' : 'text-gray-400' }}">
                                    {{ $statusLabels[$step] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Товары --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm">
            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Товары</h2>
            <div class="space-y-4">
                @foreach($order->items as $item)
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-xl bg-gray-100 overflow-hidden shrink-0">
                            @if($item->product?->mainImage)
                                <img
                                    src="{{ $item->product->mainImage->url }}"
                                    alt="{{ $item->name }}"
                                    class="w-full h-full object-cover"
                                >
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-300">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $item->name }}</p>
                            @if($item->sku)
                                <p class="text-xs text-gray-400">{{ $item->sku }}</p>
                            @endif
                            @if($item->variant_name)
                                <p class="text-xs text-gray-500">{{ $item->variant_name }}</p>
                            @endif
                            <p class="text-xs text-gray-500 mt-0.5">{{ $item->quantity }} x {{ number_format($item->price, 0, '.', ' ') }} {{ $currency }}</p>
                        </div>
                        <span class="text-sm font-semibold shrink-0">{{ number_format($item->total, 0, '.', ' ') }} {{ $currency }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Итого --}}
            <div class="mt-6 pt-4 border-t border-gray-100 space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Подитог</span>
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
                        <span class="text-gray-500">Доставка</span>
                        <span class="font-medium">{{ number_format($order->delivery_price, 0, '.', ' ') }} {{ $currency }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                    <span class="text-base font-semibold">Итого</span>
                    <span class="text-xl font-bold" style="color: var(--primary);">
                        {{ number_format($order->total, 0, '.', ' ') }} {{ $currency }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Отменён --}}
        @if($order->status === 'cancelled')
            <div class="bg-red-50 border border-red-100 rounded-2xl p-6">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <div>
                        <h3 class="text-sm font-semibold text-red-800">Заказ отменён</h3>
                        <p class="text-sm text-red-600 mt-1">Если у вас есть вопросы, свяжитесь с нами.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Кнопки --}}
        <div class="flex flex-col sm:flex-row gap-3">
            <a
                href="/store/{{ $store->slug }}"
                class="btn-primary px-8 py-3 rounded-xl text-center text-sm font-semibold"
            >
                На главную
            </a>
            <a
                href="/store/{{ $store->slug }}/catalog"
                class="px-8 py-3 rounded-xl text-center text-sm font-semibold border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors"
            >
                Продолжить покупки
            </a>
            @if($store->phone || $store->telegram)
                @if($store->telegram)
                    <a
                        href="https://t.me/{{ $store->telegram }}"
                        target="_blank"
                        rel="noopener"
                        class="px-8 py-3 rounded-xl text-center text-sm font-semibold border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/></svg>
                        Связаться
                    </a>
                @elseif($store->phone)
                    <a
                        href="tel:{{ $store->phone }}"
                        class="px-8 py-3 rounded-xl text-center text-sm font-semibold border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        Позвонить
                    </a>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
