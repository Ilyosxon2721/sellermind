@extends('storefront.layouts.app')

@section('page_title', 'Ошибка оплаты — ' . $store->name)

@section('content')
<div class="max-w-lg mx-auto px-4 py-16 text-center">
    <div class="w-20 h-20 mx-auto rounded-full bg-red-100 flex items-center justify-center mb-6">
        <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Ошибка оплаты</h1>
    <p class="text-gray-500 mb-8">К сожалению, оплата не прошла. Попробуйте ещё раз или выберите другой способ оплаты.</p>
    @if($order)
        <a href="/store/{{ $store->slug }}/order/{{ $order->order_number }}" class="inline-block px-6 py-3 rounded-xl text-white font-semibold" style="background: var(--primary);">Вернуться к заказу</a>
    @else
        <a href="/store/{{ $store->slug }}/catalog" class="inline-block px-6 py-3 rounded-xl text-white font-semibold" style="background: var(--primary);">Вернуться в каталог</a>
    @endif
</div>
@endsection
