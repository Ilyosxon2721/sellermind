@extends('storefront.layouts.app')

@section('page_title', 'Заказ ' . $order->order_number . ' — ' . $store->name)

@section('content')
@php
    $currency = $store->currency ?? 'сум';
    $slug = $store->slug;
    $statusLabels = ['new' => 'Новый', 'confirmed' => 'Подтверждён', 'processing' => 'В обработке', 'shipped' => 'Отправлен', 'delivered' => 'Доставлен', 'cancelled' => 'Отменён'];
    $statusColors = ['new' => 'bg-blue-100 text-blue-700', 'confirmed' => 'bg-green-100 text-green-700', 'processing' => 'bg-yellow-100 text-yellow-700', 'shipped' => 'bg-indigo-100 text-indigo-700', 'delivered' => 'bg-green-100 text-green-700', 'cancelled' => 'bg-red-100 text-red-700'];
@endphp

<div class="max-w-3xl mx-auto px-3 sm:px-4 lg:px-6 py-8 sm:py-12">
    <div class="text-center mb-8">
        <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-4 {{ $order->status === 'cancelled' ? 'bg-red-100' : 'bg-green-100' }}">
            @if($order->status === 'cancelled')
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            @else
                <svg class="w-8 h-8 text-green-500 animate-checkmark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            @endif
        </div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $order->status === 'cancelled' ? 'Заказ отменён' : 'Заказ оформлен!' }}</h1>
        <p class="mt-2 text-gray-500">Номер заказа: <span class="font-mono font-semibold text-gray-900">{{ $order->order_number }}</span></p>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 divide-y divide-gray-50">
        <div class="p-5 flex items-center justify-between">
            <span class="text-sm text-gray-500">Статус</span>
            <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $statusColors[$order->status] ?? 'bg-gray-100 text-gray-700' }}">
                {{ $statusLabels[$order->status] ?? $order->status }}
            </span>
        </div>
        <div class="p-5 flex items-center justify-between">
            <span class="text-sm text-gray-500">Дата</span>
            <span class="text-sm font-medium text-gray-900">{{ $order->created_at->format('d.m.Y H:i') }}</span>
        </div>

        @if($order->items->isNotEmpty())
            <div class="p-5">
                <p class="text-sm text-gray-500 mb-3">Товары</p>
                @foreach($order->items as $item)
                    <div class="flex justify-between py-1.5 text-sm">
                        <span class="text-gray-700">{{ $item->name }} <span class="text-gray-400">&times;{{ $item->quantity }}</span></span>
                        <span class="font-medium">{{ number_format((float)$item->total, 0, '.', ' ') }} {{ $currency }}</span>
                    </div>
                @endforeach
            </div>
        @endif

        @if((float)$order->delivery_price > 0)
            <div class="p-5 flex justify-between text-sm">
                <span class="text-gray-500">Доставка</span>
                <span class="font-medium">{{ number_format((float)$order->delivery_price, 0, '.', ' ') }} {{ $currency }}</span>
            </div>
        @endif

        <div class="p-5 flex justify-between text-lg font-bold">
            <span>Итого</span>
            <span>{{ number_format((float)$order->total, 0, '.', ' ') }} {{ $currency }}</span>
        </div>
    </div>

    <div class="mt-8 text-center">
        <a href="/store/{{ $slug }}/catalog" class="inline-block px-6 py-3 rounded-xl text-white font-semibold" style="background: var(--primary);">Продолжить покупки</a>
    </div>
</div>
@endsection
