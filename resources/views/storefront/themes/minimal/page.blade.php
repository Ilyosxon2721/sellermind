@extends('storefront.layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
    {{-- Хлебные крошки --}}
    <nav class="mb-8 text-sm text-gray-400">
        <a href="/store/{{ $store->slug }}" class="hover:text-gray-900 transition-colors">Главная</a>
        <span class="mx-2">/</span>
        <span class="text-gray-900">{{ $page->title }}</span>
    </nav>

    <article>
        <h1 class="text-2xl sm:text-3xl font-semibold mb-8">{{ $page->title }}</h1>

        <div class="prose prose-sm sm:prose max-w-none text-gray-600 leading-relaxed
            prose-headings:font-semibold
            prose-headings:text-gray-900
            prose-a:no-underline hover:prose-a:underline
            prose-img:rounded-lg
            prose-img:border prose-img:border-gray-200
            prose-table:text-sm
            prose-hr:border-gray-100
        " style="--tw-prose-links: var(--primary);">
            {!! $page->content !!}
        </div>
    </article>
</div>
@endsection
