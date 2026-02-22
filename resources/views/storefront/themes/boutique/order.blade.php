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
        'new' => 'bg-blue-50 text-blue-700 border border-blue-200',
        'confirmed' => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
        'processing' => 'bg-yellow-50 text-yellow-700 border border-yellow-200',
        'shipped' => 'bg-purple-50 text-purple-700 border border-purple-200',
        'delivered' => 'bg-green-50 text-green-700 border border-green-200',
        'cancelled' => 'bg-red-50 text-red-700 border border-red-200',
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

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
    {{-- Успешное оформление --}}
    @if($order->status === 'new')
        <div class="text-center mb-14">
            <div class="w-24 h-24 mx-auto rounded-full flex items-center justify-center mb-6 shadow-lg" style="background: linear-gradient(135deg, var(--primary), var(--secondary));">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight mb-3">Заказ оформлен!</h1>
            <p class="text-gray-400 max-w-md mx-auto">Спасибо за заказ. Мы свяжемся с вами для подтверждения.</p>
            {{-- Декоративный разделитель --}}
            <div class="flex items-center justify-center gap-3 mt-8">
                <div class="w-12 h-px bg-gray-200"></div>
                <svg class="w-3 h-3 text-gray-300" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7L12 16.4 5.7 21l2.3-7L2 9.4h7.6z"/></svg>
                <div class="w-12 h-px bg-gray-200"></div>
            </div>
        </div>
    @else
        <div class="mb-10">
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight">
                Заказ {{ $order->order_number }}
            </h1>
            <div class="mt-3 w-12 h-0.5" style="background: var(--primary);"></div>
        </div>
    @endif

    {{-- Информация о заказе --}}
    <div class="space-y-7">
        {{-- Шапка заказа --}}
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-md">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-7">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.15em] text-gray-400 mb-1">Номер заказа</p>
                    <p class="text-xl font-bold">{{ $order->order_number }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex px-4 py-2 rounded-2xl text-xs font-semibold {{ $statusColors[$order->status] ?? 'bg-gray-50 text-gray-700 border border-gray-200' }}">
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                    @if($order->payment_status)
                        <span class="inline-flex px-4 py-2 rounded-2xl text-xs font-semibold {{ $order->payment_status === 'paid' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-gray-50 text-gray-700 border border-gray-200' }}">
                            {{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.15em] text-gray-400 mb-1">Дата заказа</p>
                    <p class="font-medium">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.15em] text-gray-400 mb-1">Покупатель</p>
                    <p class="font-medium">{{ $order->customer_name }}</p>
                    <p class="text-gray-400 text-xs mt-0.5">{{ $order->customer_phone }}</p>
                </div>
                @if($order->deliveryMethod)
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.15em] text-gray-400 mb-1">Доставка</p>
                        <p class="font-medium">{{ $order->deliveryMethod->name }}</p>
                        @if($order->delivery_address)
                            <p class="text-gray-400 text-xs mt-0.5">
                                {{ $order->delivery_city ? $order->delivery_city . ', ' : '' }}{{ $order->delivery_address }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Таймлайн статуса --}}
        @if($order->status !== 'cancelled')
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-md">
                <h2 class="text-xs font-bold uppercase tracking-[0.15em] text-gray-400 mb-8">Статус заказа</h2>
                <div class="relative">
                    {{-- Линия (desktop) --}}
                    <div class="absolute top-5 left-5 right-5 h-0.5 bg-gray-100 hidden sm:block">
                        @if($currentStepIndex > 0)
                            <div
                                class="absolute top-0 left-0 h-full rounded-full transition-all duration-700"
                                style="background: linear-gradient(90deg, var(--primary), var(--secondary)); width: {{ ($currentStepIndex / (count($timelineSteps) - 1)) * 100 }}%;"
                            ></div>
                        @endif
                    </div>

                    <div class="flex flex-col sm:flex-row sm:justify-between gap-5 sm:gap-0 relative">
                        @foreach($timelineSteps as $stepIndex => $step)
                            <div class="flex sm:flex-col items-center gap-3 sm:gap-2.5 sm:w-1/5">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 transition-all duration-500 relative z-10"
                                    @if($stepIndex <= $currentStepIndex)
                                        style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; box-shadow: 0 4px 12px color-mix(in srgb, var(--primary) 30%, transparent);"
                                    @else
                                        style="background: #f9fafb; color: #d1d5db;"
                                    @endif
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
                                <span class="text-xs font-medium {{ $stepIndex <= $currentStepIndex ? 'text-gray-900' : 'text-gray-300' }}">
                                    {{ $statusLabels[$step] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Товары --}}
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-md">
            <h2 class="text-xs font-bold uppercase tracking-[0.15em] text-gray-400 mb-6">Товары</h2>
            <div class="space-y-5">
                @foreach($order->items as $item)
                    <div class="flex items-center gap-5">
                        <div class="w-16 h-16 rounded-2xl bg-gray-50 overflow-hidden shrink-0 shadow-sm">
                            @if($item->product?->mainImage)
                                <img
                                    src="{{ $item->product->mainImage->url }}"
                                    alt="{{ $item->name }}"
                                    class="w-full h-full object-cover"
                                >
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-200">
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $item->name }}</p>
                            @if($item->sku)
                                <p class="text-xs text-gray-300">{{ $item->sku }}</p>
                            @endif
                            @if($item->variant_name)
                                <p class="text-xs text-gray-400">{{ $item->variant_name }}</p>
                            @endif
                            <p class="text-xs text-gray-400 mt-0.5">{{ $item->quantity }} x {{ number_format($item->price, 0, '.', ' ') }} {{ $currency }}</p>
                        </div>
                        <span class="text-sm font-bold shrink-0">{{ number_format($item->total, 0, '.', ' ') }} {{ $currency }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Декоративный разделитель --}}
            <div class="flex items-center gap-3 mt-7 mb-5">
                <div class="flex-1 h-px bg-gray-100"></div>
                <svg class="w-2.5 h-2.5 text-gray-200" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7L12 16.4 5.7 21l2.3-7L2 9.4h7.6z"/></svg>
                <div class="flex-1 h-px bg-gray-100"></div>
            </div>

            {{-- Итого --}}
            <div class="space-y-2.5">
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
                    <span class="text-lg font-bold">Итого</span>
                    <span class="text-2xl font-bold" style="color: var(--primary);">
                        {{ number_format($order->total, 0, '.', ' ') }} {{ $currency }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Кнопки --}}
        <div class="flex flex-col sm:flex-row gap-4">
            <a
                href="/store/{{ $store->slug }}"
                class="px-10 py-3.5 rounded-2xl text-center text-sm font-semibold text-white transition-all duration-300 hover:shadow-xl hover:brightness-110"
                style="background: linear-gradient(135deg, var(--primary), var(--secondary));"
            >
                На главную
            </a>
            <a
                href="/store/{{ $store->slug }}/catalog"
                class="px-10 py-3.5 rounded-2xl text-center text-sm font-semibold border-2 transition-all duration-300 hover:shadow-md"
                style="border-color: var(--primary); color: var(--primary);"
            >
                Продолжить покупки
            </a>
        </div>
    </div>
</div>
@endsection
