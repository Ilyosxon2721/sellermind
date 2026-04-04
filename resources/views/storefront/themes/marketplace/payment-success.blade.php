@extends('storefront.layouts.app')

@section('page_title', 'Оплата прошла успешно — ' . $store->name)

@section('content')
<div class="max-w-lg mx-auto px-4 py-16 text-center">
    <div class="w-20 h-20 mx-auto rounded-full bg-green-100 flex items-center justify-center mb-6">
        <svg class="w-10 h-10 text-green-500 animate-checkmark" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 mb-2">Оплата прошла успешно!</h1>
    <p class="text-gray-500 mb-8">Спасибо за покупку. Мы свяжемся с вами для подтверждения заказа.</p>
    @if($order)
        <p class="text-sm text-gray-500 mb-6">Номер заказа: <span class="font-mono font-semibold text-gray-900">{{ $order->order_number }}</span></p>
        <a href="/store/{{ $store->slug }}/order/{{ $order->order_number }}" class="inline-block px-6 py-3 rounded-xl text-white font-semibold mb-3" style="background: var(--primary);">Статус заказа</a>
    @endif
    <a href="/store/{{ $store->slug }}/catalog" class="block text-sm font-medium mt-4" style="color: var(--primary);">Продолжить покупки</a>
</div>
@endsection
