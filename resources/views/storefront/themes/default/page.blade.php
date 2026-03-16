@extends('storefront.layouts.app')

@section('page_title', $page->title . ' — ' . $store->name)
@if($page->meta_description ?? null)
    @section('meta_description', $page->meta_description)
@endif

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    {{-- Хлебные крошки --}}
    <nav class="mb-6 text-sm text-gray-500">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Главная</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">{{ $page->title }}</span>
    </nav>

    <article class="bg-white rounded-2xl p-6 sm:p-10 shadow-sm">
        <h1 class="text-2xl sm:text-3xl font-bold mb-6">{{ $page->title }}</h1>

        <div class="prose prose-sm sm:prose max-w-none text-gray-700 leading-relaxed
            prose-headings:font-semibold
            prose-a:no-underline hover:prose-a:underline
            prose-img:rounded-xl
            prose-table:text-sm
        " style="--tw-prose-links: var(--primary);">
            {!! $page->content !!}
        </div>
    </article>

    {{-- Навигация --}}
    <div class="mt-6 flex justify-between">
        <a href="/store/{{ $store->slug }}" class="inline-flex items-center gap-2 text-sm font-medium hover:opacity-75 transition-opacity" style="color: var(--primary);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            На главную
        </a>
        <a href="/store/{{ $store->slug }}/catalog" class="inline-flex items-center gap-2 text-sm font-medium hover:opacity-75 transition-opacity" style="color: var(--primary);">
            Каталог
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>
@endsection
