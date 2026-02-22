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
        'new' => 'bg-blue-900/10 text-blue-700 border border-blue-200',
        'confirmed' => 'bg-indigo-900/10 text-indigo-700 border border-indigo-200',
        'processing' => 'bg-yellow-900/10 text-yellow-700 border border-yellow-200',
        'shipped' => 'bg-purple-900/10 text-purple-700 border border-purple-200',
        'delivered' => 'bg-green-900/10 text-green-700 border border-green-200',
        'cancelled' => 'bg-red-900/10 text-red-700 border border-red-200',
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

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    {{-- Успешное оформление --}}
    @if($order->status === 'new')
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto rounded-lg flex items-center justify-center mb-4" style="background: var(--primary);">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold uppercase tracking-wider mb-1">Заказ оформлен</h1>
            <p class="text-sm text-gray-500 font-mono">Мы свяжемся с вами для подтверждения</p>
        </div>
    @else
        <div class="flex items-center gap-3 mb-6">
            <div class="w-1 h-6 rounded-sm" style="background: var(--primary);"></div>
            <h1 class="text-xl sm:text-2xl font-bold uppercase tracking-wider">
                Заказ {{ $order->order_number }}
            </h1>
        </div>
    @endif

    <div class="space-y-5">
        {{-- Шапка заказа --}}
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-900 text-white px-4 py-2.5 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-xs font-mono text-gray-400">ORDER</span>
                    <span class="text-sm font-mono font-bold">{{ $order->order_number }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex px-2.5 py-1 rounded text-xs font-mono font-bold {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                    @if($order->payment_status)
                        <span class="inline-flex px-2.5 py-1 rounded text-xs font-mono font-bold {{ $order->payment_status === 'paid' ? 'bg-green-900/10 text-green-700 border border-green-200' : 'bg-gray-100 text-gray-600' }}">
                            {{ $paymentLabels[$order->payment_status] ?? $order->payment_status }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="p-4 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div class="border-l-4 pl-3" style="border-color: var(--primary);">
                    <p class="text-xs font-mono uppercase tracking-widest text-gray-400">Дата</p>
                    <p class="font-mono font-semibold mt-0.5">{{ $order->created_at->format('d.m.Y H:i') }}</p>
                </div>
                <div class="border-l-4 pl-3" style="border-color: var(--primary);">
                    <p class="text-xs font-mono uppercase tracking-widest text-gray-400">Покупатель</p>
                    <p class="font-semibold mt-0.5">{{ $order->customer_name }}</p>
                    <p class="text-xs text-gray-400 font-mono">{{ $order->customer_phone }}</p>
                </div>
                @if($order->deliveryMethod)
                    <div class="border-l-4 pl-3" style="border-color: var(--primary);">
                        <p class="text-xs font-mono uppercase tracking-widest text-gray-400">Доставка</p>
                        <p class="font-semibold mt-0.5">{{ $order->deliveryMethod->name }}</p>
                        @if($order->delivery_address)
                            <p class="text-xs text-gray-400 font-mono">
                                {{ $order->delivery_city ? $order->delivery_city . ', ' : '' }}{{ $order->delivery_address }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Таймлайн статуса (горизонтальный) --}}
        @if($order->status !== 'cancelled')
            <div class="border border-gray-200 rounded-lg p-4">
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-4 font-mono">Статус заказа</h2>
                <div class="relative">
                    {{-- Линия --}}
                    <div class="absolute top-4 left-4 right-4 h-px bg-gray-200 hidden sm:block">
                        @if($currentStepIndex > 0)
                            <div
                                class="absolute top-0 left-0 h-full transition-all duration-500"
                                style="background: var(--primary); width: {{ ($currentStepIndex / (count($timelineSteps) - 1)) * 100 }}%;"
                            ></div>
                        @endif
                    </div>

                    <div class="flex flex-col sm:flex-row sm:justify-between gap-3 sm:gap-0 relative">
                        @foreach($timelineSteps as $stepIndex => $step)
                            <div class="flex sm:flex-col items-center gap-2 sm:gap-1.5 sm:w-1/5">
                                <div
                                    class="w-8 h-8 rounded flex items-center justify-center shrink-0 transition-colors duration-300 relative z-10 text-xs font-mono font-bold"
                                    style="{{ $stepIndex <= $currentStepIndex ? 'background: var(--primary); color: white;' : 'background: #f3f4f6; color: #9ca3af;' }}"
                                >
                                    @if($stepIndex < $currentStepIndex)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @else
                                        {{ $stepIndex + 1 }}
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

        {{-- Товары (таблица) --}}
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-900 text-white px-4 py-2.5">
                <h2 class="text-xs font-bold uppercase tracking-wider font-mono">Товары</h2>
            </div>

            {{-- Desktop таблица --}}
            <div class="hidden sm:block">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-xs font-mono uppercase tracking-wider text-gray-400 border-b border-gray-200">
                            <th class="text-left px-4 py-2">Товар</th>
                            <th class="text-center px-3 py-2 w-20">Кол-во</th>
                            <th class="text-right px-3 py-2 w-28">Цена</th>
                            <th class="text-right px-4 py-2 w-28">Сумма</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $index => $item)
                            <tr class="border-b border-gray-100 {{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                                <td class="px-4 py-2.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded border border-gray-200 bg-gray-100 overflow-hidden shrink-0">
                                            @if($item->product?->mainImage)
                                                <img
                                                    src="{{ $item->product->mainImage->url }}"
                                                    alt="{{ $item->name }}"
                                                    class="w-full h-full object-contain p-0.5"
                                                >
                                            @else
                                                <div class="w-full h-full flex items-center justify-center text-gray-300">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900 text-sm">{{ $item->name }}</p>
                                            @if($item->sku)
                                                <p class="text-xs text-gray-400 font-mono">{{ $item->sku }}</p>
                                            @endif
                                            @if($item->variant_name)
                                                <p class="text-xs text-gray-500">{{ $item->variant_name }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 text-center font-mono">{{ $item->quantity }}</td>
                                <td class="px-3 py-2.5 text-right font-mono">{{ number_format($item->price, 0, '.', ' ') }}</td>
                                <td class="px-4 py-2.5 text-right font-mono font-bold">{{ number_format($item->total, 0, '.', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile карточки --}}
            <div class="sm:hidden divide-y divide-gray-100">
                @foreach($order->items as $item)
                    <div class="p-3 flex gap-3">
                        <div class="w-12 h-12 rounded border border-gray-200 bg-gray-100 overflow-hidden shrink-0">
                            @if($item->product?->mainImage)
                                <img src="{{ $item->product->mainImage->url }}" alt="{{ $item->name }}" class="w-full h-full object-contain p-0.5">
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $item->name }}</p>
                            @if($item->sku)
                                <p class="text-xs text-gray-400 font-mono">{{ $item->sku }}</p>
                            @endif
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-gray-500 font-mono">{{ $item->quantity }} x {{ number_format($item->price, 0, '.', ' ') }}</span>
                                <span class="text-sm font-mono font-bold">{{ number_format($item->total, 0, '.', ' ') }} {{ $currency }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Итого --}}
            <div class="border-t-2 border-gray-200 p-4 space-y-1.5">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-500 font-mono uppercase">Подитог</span>
                    <span class="font-mono font-semibold">{{ number_format($order->subtotal, 0, '.', ' ') }} {{ $currency }}</span>
                </div>
                @if((float)$order->discount > 0)
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-green-600 font-mono uppercase">Скидка</span>
                        <span class="font-mono font-semibold text-green-600">-{{ number_format($order->discount, 0, '.', ' ') }} {{ $currency }}</span>
                    </div>
                @endif
                @if((float)$order->delivery_price > 0)
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-gray-500 font-mono uppercase">Доставка</span>
                        <span class="font-mono font-semibold">{{ number_format($order->delivery_price, 0, '.', ' ') }} {{ $currency }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between pt-2 border-t-2 border-gray-900">
                    <span class="text-sm font-bold uppercase tracking-wider">Итого</span>
                    <span class="text-xl font-bold font-mono" style="color: var(--primary);">
                        {{ number_format($order->total, 0, '.', ' ') }} {{ $currency }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Кнопки --}}
        <div class="flex flex-col sm:flex-row gap-2">
            <a
                href="/store/{{ $store->slug }}"
                class="btn-primary px-6 py-2.5 rounded-lg text-center text-xs font-bold uppercase tracking-wider"
            >
                На главную
            </a>
            <a
                href="/store/{{ $store->slug }}/catalog"
                class="px-6 py-2.5 rounded-lg text-center text-xs font-bold uppercase tracking-wider border border-gray-200 text-gray-700 hover:bg-gray-50 transition-colors"
            >
                Продолжить покупки
            </a>
        </div>
    </div>
</div>
@endsection
