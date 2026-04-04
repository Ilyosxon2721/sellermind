@extends('storefront.layouts.app')

@section('page_title', ($page->meta_title ?? $page->title) . ' — ' . $store->name)
@if($page->meta_description)
    @section('meta_description', $page->meta_description)
@endif

@section('content')
@php $slug = $store->slug; @endphp

<div class="max-w-4xl mx-auto px-3 sm:px-4 lg:px-6 py-6 sm:py-10">
    <nav class="mb-4 text-sm text-gray-400 flex items-center gap-1.5">
        <a href="/store/{{ $slug }}" class="hover:text-gray-600">Главная</a>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600">{{ $page->title }}</span>
    </nav>

    <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">{{ $page->title }}</h1>

    <div class="prose prose-gray max-w-none text-gray-700 leading-relaxed">
        {!! $page->content !!}
    </div>
</div>
@endsection
