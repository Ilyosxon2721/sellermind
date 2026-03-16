@extends('storefront.layouts.app')

@section('page_title', 'Оплата прошла успешно — ' . $store->name)

@section('content')
<div class="min-h-[60vh] flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <div class="w-20 h-20 mx-auto mb-6 bg-green-100 rounded-full flex items-center justify-center animate-checkmark">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Оплата прошла успешно!</h1>
        <p class="text-gray-600 mb-4">Спасибо за покупку. Ваш заказ принят в обработку.</p>
        @if(isset($order) && $order)
            <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                <p class="text-sm text-gray-500">Номер заказа</p>
                <p class="text-lg font-semibold text-gray-900">{{ $order->order_number }}</p>
                <p class="text-sm text-gray-500 mt-2">Сумма: {{ number_format((float)$order->total, 0, '', ' ') }} {{ $store->currency ?? 'UZS' }}</p>
            </div>

            <div class="bg-blue-50 rounded-xl p-4 mb-6 text-left">
                <h3 class="text-sm font-semibold text-blue-900 mb-1">Что дальше?</h3>
                <p class="text-sm text-blue-700">Мы обработаем ваш заказ и свяжемся с вами для подтверждения доставки.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="/store/{{ $store->slug }}/order/{{ $order->order_number }}"
                   class="inline-flex items-center justify-center px-6 py-3 btn-primary rounded-xl font-medium">
                    Статус заказа
                </a>
                <a href="/store/{{ $store->slug }}"
                   class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition-colors">
                    Вернуться в магазин
                </a>
            </div>
        @else
            <a href="/store/{{ $store->slug }}"
               class="inline-flex items-center px-6 py-3 btn-primary rounded-xl font-medium">
                Вернуться в магазин
            </a>
        @endif
    </div>
</div>
@endsection
