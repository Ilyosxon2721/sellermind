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
        'new' => 'bg-blue-100 text-blue-700 border-blue-200',
        'confirmed' => 'bg-indigo-100 text-indigo-700 border-indigo-200',
        'processing' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
        'shipped' => 'bg-purple-100 text-purple-700 border-purple-200',
        'delivered' => 'bg-green-100 text-green-700 border-green-200',
        'cancelled' => 'bg-red-100 text-red-700 border-red-200',
    ];

    $statusIconColors = [
        'new' => 'text-blue-500',
        'confirmed' => 'text-indigo-500',
        'processing' => 'text-yellow-500',
        'shipped' => 'text-purple-500',
        'delivered' => 'text-green-500',
        'cancelled' => 'text-red-500',
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
            <div class="relative w-24 h-24 mx-auto mb-6">
                <div class="absolute inset-0 rounded-full animate-ping opacity-20" style="background: var(--primary);"></div>
                <div class="relative w-24 h-24 rounded-full flex items-center justify-center bg-green-100">
                    <svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold mb-3">Заказ оформлен!</h1>
            <p class="text-gray-500 text-lg">Спасибо за заказ. Мы свяжемся с вами для подтверждения.</p>
        </div>
    @else
        <h1 class="text-2xl sm:text-3xl font-bold mb-8 flex items-center gap-3">
            <svg class="w-7 h-7" style="color: var(--primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            Заказ {{ $order->order_number }}
        </h1>
    @endif

    <div class="space-y-6">
        {{-- Шапка заказа --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <p class="text-sm text-gray-500">Номер заказа</p>
                    <p class="text-xl font-bold" style="color: var(--primary);">{{ $order->order_number }}</p>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold border {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700 border-gray-200' }}">
                        <span class="w-2 h-2 rounded-full {{ $statusIconColors[$order->status] ?? 'text-gray-500' }}" style="background: currentColor;"></span>
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                    @if($order->payment_status)
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-xs font-bold border {{ $order->payment_status === 'paid' ? 'bg-green-100 text-green-700 border-green-200' : 'bg-gray-100 text-gray-700 border-gray-200' }}">
                            {{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Дата заказа</p>
                    <p class="font-bold text-gray-900 mt-1">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                </div>
                <div class="bg-gray-50 rounded-xl p-4">
                    <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Покупатель</p>
                    <p class="font-bold text-gray-900 mt-1">{{ $order->customer_name }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $order->customer_phone }}</p>
                </div>
                @if($order->deliveryMethod)
                    <div class="bg-gray-50 rounded-xl p-4">
                        <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Доставка</p>
                        <p class="font-bold text-gray-900 mt-1">{{ $order->deliveryMethod->name }}</p>
                        @if($order->delivery_address)
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $order->delivery_city ? $order->delivery_city . ', ' : '' }}{{ $order->delivery_address }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Таймлайн статуса --}}
        @if($order->status !== 'cancelled')
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500 mb-6">Статус заказа</h2>
                <div class="relative">
                    {{-- Линия --}}
                    <div class="absolute top-5 left-5 right-5 h-1 bg-gray-200 rounded-full hidden sm:block">
                        @if($currentStepIndex > 0)
                            <div
                                class="absolute top-0 left-0 h-full rounded-full transition-all duration-500"
                                style="background: var(--primary); width: {{ ($currentStepIndex / (count($timelineSteps) - 1)) * 100 }}%;"
                            ></div>
                        @endif
                    </div>

                    <div class="flex flex-col sm:flex-row sm:justify-between gap-4 sm:gap-0 relative">
                        @foreach($timelineSteps as $stepIndex => $step)
                            <div class="flex sm:flex-col items-center gap-3 sm:gap-2 sm:w-1/5">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 transition-colors duration-300 relative z-10 shadow-sm"
                                    style="{{ $stepIndex <= $currentStepIndex ? 'background: var(--primary); color: white;' : 'background: #f3f4f6; color: #9ca3af;' }}"
                                >
                                    @if($stepIndex < $currentStepIndex)
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @elseif($stepIndex === $currentStepIndex)
                                        <div class="w-3 h-3 rounded-full bg-white"></div>
                                    @else
                                        <span class="text-xs font-bold">{{ $stepIndex + 1 }}</span>
                                    @endif
                                </div>
                                <span class="text-xs font-semibold {{ $stepIndex <= $currentStepIndex ? 'text-gray-900' : 'text-gray-400' }} sm:text-center">
                                    {{ $statusLabels[$step] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Товары --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500 mb-5">Товары</h2>
            <div class="space-y-4">
                @foreach($order->items as $item)
                    <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-xl">
                        <div class="w-16 h-16 rounded-xl bg-white overflow-hidden shrink-0 border border-gray-100 p-1">
                            @if($item->product?->mainImage)
                                <img
                                    src="{{ $item->product->mainImage->url }}"
                                    alt="{{ $item->name }}"
                                    class="w-full h-full object-contain"
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
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $item->name }}</p>
                            @if($item->sku)
                                <p class="text-xs text-gray-400">{{ $item->sku }}</p>
                            @endif
                            @if($item->variant_name)
                                <p class="text-xs text-gray-500">{{ $item->variant_name }}</p>
                            @endif
                            <p class="text-xs text-gray-500 mt-0.5">{{ $item->quantity }} x {{ number_format($item->price, 0, '.', ' ') }} {{ $currency }}</p>
                        </div>
                        <span class="text-sm font-bold shrink-0" style="color: var(--primary);">{{ number_format($item->total, 0, '.', ' ') }} {{ $currency }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Итого --}}
            <div class="mt-6 pt-4 border-t border-gray-100 space-y-2">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">Подитог</span>
                    <span class="font-semibold">{{ number_format($order->subtotal, 0, '.', ' ') }} {{ $currency }}</span>
                </div>
                @if((float)$order->discount > 0)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-green-600 font-medium">Скидка</span>
                        <span class="font-semibold text-green-600">-{{ number_format($order->discount, 0, '.', ' ') }} {{ $currency }}</span>
                    </div>
                @endif
                @if((float)$order->delivery_price > 0)
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500">Доставка</span>
                        <span class="font-semibold">{{ number_format($order->delivery_price, 0, '.', ' ') }} {{ $currency }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between pt-3 border-t-2 border-gray-200">
                    <span class="text-lg font-bold">Итого</span>
                    <span class="text-2xl font-bold" style="color: var(--primary);">
                        {{ number_format($order->total, 0, '.', ' ') }} {{ $currency }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Кнопки --}}
        <div class="flex flex-col sm:flex-row gap-3">
            <a
                href="/store/{{ $store->slug }}"
                class="btn-primary px-10 py-3.5 rounded-full text-center text-base font-bold flex items-center justify-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                На главную
            </a>
            <a
                href="/store/{{ $store->slug }}/catalog"
                class="px-10 py-3.5 rounded-full text-center text-base font-bold border-2 border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors flex items-center justify-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
                Продолжить покупки
            </a>
        </div>
    </div>
</div>
@endsection
