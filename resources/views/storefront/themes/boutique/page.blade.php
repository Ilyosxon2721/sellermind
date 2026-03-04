@extends('storefront.layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
    {{-- Хлебные крошки --}}
    <nav class="mb-8 text-sm text-gray-400 flex items-center gap-2">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Главная</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700 font-medium">{{ $page->title }}</span>
    </nav>

    <article class="bg-white rounded-3xl p-8 sm:p-12 shadow-lg">
        {{-- Заголовок с декором --}}
        <div class="text-center mb-10 max-w-3xl mx-auto">
            <div class="flex items-center justify-center gap-3 mb-5">
                <div class="w-8 h-px bg-gray-300"></div>
                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2l2.4 7.4H22l-6 4.6 2.3 7L12 16.4 5.7 21l2.3-7L2 9.4h7.6z"/></svg>
                <div class="w-8 h-px bg-gray-300"></div>
            </div>
            <h1 class="text-3xl sm:text-4xl font-bold tracking-tight">{{ $page->title }}</h1>
            <div class="mt-4 w-12 h-0.5 mx-auto" style="background: var(--primary);"></div>
        </div>

        <div class="prose prose-lg max-w-3xl mx-auto text-gray-600 leading-relaxed
            prose-headings:font-bold prose-headings:tracking-tight
            prose-a:no-underline hover:prose-a:underline
            prose-img:rounded-2xl prose-img:shadow-md
            prose-table:text-sm
            prose-blockquote:border-l-4 prose-blockquote:rounded-r-2xl prose-blockquote:bg-gray-50 prose-blockquote:py-2 prose-blockquote:px-4
        " style="--tw-prose-links: var(--primary); --tw-prose-quote-borders: var(--primary);">
            {!! $page->content !!}
        </div>
    </article>
</div>
@endsection
