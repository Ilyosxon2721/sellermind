@extends('storefront.layouts.app')

@section('page_title', 'Ошибка оплаты — ' . $store->name)

@section('content')
<div class="min-h-[60vh] flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <div class="w-20 h-20 mx-auto mb-6 bg-red-100 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Ошибка оплаты</h1>
        <p class="text-gray-600 mb-4">К сожалению, оплата не прошла. Попробуйте ещё раз или выберите другой способ оплаты.</p>
        @if(isset($order) && $order)
            <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                <p class="text-sm text-gray-500">Номер заказа</p>
                <p class="text-lg font-semibold text-gray-900">{{ $order->order_number }}</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="/store/{{ $store->slug }}/order/{{ $order->order_number }}"
                   class="inline-flex items-center justify-center px-6 py-3 btn-primary rounded-xl font-medium">
                    Перейти к заказу
                </a>
                <a href="/store/{{ $store->slug }}"
                   class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition-colors">
                    Вернуться в магазин
                </a>
            </div>

            @if($store->phone || $store->telegram)
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <p class="text-sm text-gray-500 mb-2">Нужна помощь?</p>
                    <div class="flex items-center justify-center gap-3">
                        @if($store->phone)
                            <a href="tel:{{ $store->phone }}" class="text-sm font-medium hover:opacity-75 transition-opacity" style="color: var(--primary);">
                                {{ $store->phone }}
                            </a>
                        @endif
                        @if($store->telegram)
                            <a href="https://t.me/{{ $store->telegram }}" target="_blank" rel="noopener" class="text-sm font-medium hover:opacity-75 transition-opacity" style="color: var(--primary);">
                                Telegram
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        @else
            <a href="/store/{{ $store->slug }}"
               class="inline-flex items-center px-6 py-3 btn-primary rounded-xl font-medium">
                Вернуться в магазин
            </a>
        @endif
    </div>
</div>
@endsection
