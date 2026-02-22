@extends('storefront.layouts.app')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center px-4 py-16">
    <div class="bg-white rounded-2xl shadow-sm p-8 max-w-md w-full text-center">
        <div class="w-20 h-20 mx-auto mb-6 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Оплата прошла успешно!</h1>
        <p class="text-gray-600 mb-8">Спасибо за покупку. Ваш заказ принят в обработку.</p>
        <a href="/store/{{ $store->slug }}"
           class="inline-flex items-center px-8 py-3 text-white rounded-full font-medium text-sm hover:shadow-lg transition-all"
           style="background: var(--primary)">
            Вернуться в магазин
        </a>
    </div>
</div>
@endsection
