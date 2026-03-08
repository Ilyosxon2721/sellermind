@extends('storefront.layouts.app')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center px-4">
    <div class="text-center max-w-sm">
        <div class="w-16 h-16 mx-auto mb-6 rounded-full border-2 border-red-500 flex items-center justify-center">
            <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <h1 class="text-xl font-semibold text-gray-900 mb-2">Ошибка оплаты</h1>
        <p class="text-sm text-gray-400 mb-10">Оплата не прошла. Попробуйте ещё раз или выберите другой способ оплаты.</p>
        <a
            href="/store/{{ $store->slug }}"
            class="inline-block px-8 py-2.5 border border-gray-900 text-gray-900 rounded text-sm font-medium hover:bg-gray-900 hover:text-white transition-colors"
        >
            Вернуться в магазин
        </a>
    </div>
</div>
@endsection
