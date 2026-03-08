@extends('storefront.layouts.app')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center px-4 py-16">
    <div class="text-center max-w-lg">
        <div class="bg-white rounded-3xl p-10 sm:p-14 shadow-lg">
            {{-- Иконка в градиентном круге --}}
            <div class="w-24 h-24 mx-auto mb-8 rounded-full flex items-center justify-center shadow-lg" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>

            {{-- Декоративный орнамент --}}
            <div class="flex items-center justify-center gap-3 mb-6">
                <div class="w-8 h-px bg-gray-200"></div>
                <svg class="w-3 h-3 text-gray-300" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7L12 16.4 5.7 21l2.3-7L2 9.4h7.6z"/></svg>
                <div class="w-8 h-px bg-gray-200"></div>
            </div>

            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight mb-3">Оплата прошла успешно!</h1>
            <p class="text-gray-400 leading-relaxed mb-10">Спасибо за покупку. Ваш заказ принят в обработку.</p>

            <a
                href="/store/{{ $store->slug }}"
                class="inline-flex items-center gap-2 px-10 py-4 rounded-2xl text-white font-semibold transition-all duration-300 hover:shadow-xl hover:scale-105 hover:brightness-110"
                style="background: linear-gradient(135deg, var(--primary), var(--secondary));"
            >
                Вернуться в магазин
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</div>
@endsection
