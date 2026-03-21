@extends('storefront.layouts.app')

@section('page_title', 'Избранное — ' . $store->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <nav class="mb-6 text-sm text-gray-500">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Главная</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">Избранное</span>
    </nav>

    <h1 class="text-2xl sm:text-3xl font-bold mb-8">Избранное</h1>

    @include('storefront.components.wishlist-page-content', ['store' => $store])
</div>
@endsection
