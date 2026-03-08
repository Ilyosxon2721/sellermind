@extends('storefront.layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
    {{-- Хлебные крошки --}}
    <nav class="flex items-center space-x-2 text-sm mb-6">
        <a href="/store/{{ $store->slug }}" class="flex items-center" style="color: var(--primary)">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            Главная
        </a>
        <span class="text-gray-400">/</span>
        <span class="text-gray-600">{{ $page->title }}</span>
    </nav>

    {{-- Содержимое --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 sm:p-10">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">{{ $page->title }}</h1>
        <div class="prose prose-sm sm:prose max-w-none" style="--tw-prose-links: var(--primary)">
            {!! $page->content !!}
        </div>
    </div>
</div>
@endsection
