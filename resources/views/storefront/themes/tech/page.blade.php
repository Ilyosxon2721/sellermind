@extends('storefront.layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
    {{-- Хлебные крошки --}}
    <nav class="mb-4 text-xs font-mono text-gray-400">
        <a href="/store/{{ $store->slug }}" class="hover:opacity-75 transition-opacity" style="color: var(--primary);">Главная</a>
        <span class="mx-1.5">/</span>
        <span class="text-gray-700">{{ $page->title }}</span>
    </nav>

    <article class="border border-gray-200 rounded-lg overflow-hidden">
        <div class="bg-gray-900 text-white px-6 py-4">
            <h1 class="text-lg sm:text-xl font-bold uppercase tracking-wider">{{ $page->title }}</h1>
        </div>

        <div class="p-6 sm:p-8">
            <div class="prose prose-sm sm:prose max-w-none text-gray-700 leading-relaxed
                prose-headings:font-bold
                prose-headings:uppercase
                prose-headings:tracking-wider
                prose-a:no-underline hover:prose-a:underline
                prose-img:rounded-lg
                prose-table:text-sm
                prose-table:border
                prose-th:bg-gray-900
                prose-th:text-white
                prose-th:font-mono
                prose-th:text-xs
                prose-th:uppercase
                prose-td:border
                prose-td:font-mono
                prose-code:font-mono
                prose-code:text-sm
                prose-code:bg-gray-100
                prose-code:px-1.5
                prose-code:py-0.5
                prose-code:rounded
            " style="--tw-prose-links: var(--primary);">
                {!! $page->content !!}
            </div>
        </div>
    </article>
</div>
@endsection
