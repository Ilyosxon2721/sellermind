@extends('layouts.app')

@section('content')
    @if(($accountMarketplace ?? '') === 'uzum')
        @include('pages.marketplace.partials.products_uzum', ['accountId' => $accountId])
    @elseif(($accountMarketplace ?? '') === 'ym' || ($accountMarketplace ?? '') === 'yandex_market')
        @include('pages.marketplace.partials.products_ym', ['accountId' => $accountId])
    @elseif(($accountMarketplace ?? '') === 'ozon')
        @include('pages.marketplace.partials.products_ozon', ['accountId' => $accountId])
    @else
        @include('pages.marketplace.partials.products_wb', ['accountId' => $accountId])
    @endif
@endsection
