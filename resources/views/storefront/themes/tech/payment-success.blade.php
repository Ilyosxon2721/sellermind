@extends('storefront.layouts.app')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center px-4 py-16">
    <div class="bg-white border border-gray-200 rounded-lg p-8 max-w-md w-full text-center">
        <div class="w-16 h-16 mx-auto mb-6 bg-green-100 rounded-lg flex items-center justify-center">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-xl font-bold text-gray-900 mb-2 font-mono">ОПЛАТА УСПЕШНА</h1>
        <p class="text-sm text-gray-600 mb-6">Ваш заказ принят в обработку. Спасибо за покупку!</p>
        <a href="/store/{{ $store->slug }}"
           class="inline-flex items-center px-6 py-2.5 text-sm font-medium text-white rounded-lg transition-colors"
           style="background: var(--primary)">
            Вернуться в магазин
        </a>
    </div>
</div>
@endsection
