@extends('layouts.app')

@section('content')
@php
$statuses = [
    ['value' => 'all', 'label' => 'Все', 'count' => 0],
    ['value' => 'awaiting_packaging', 'label' => 'Ожидает упаковки', 'count' => 0],
    ['value' => 'awaiting_deliver', 'label' => 'Ждет отгрузки', 'count' => 0],
    ['value' => 'delivering', 'label' => 'Доставляется', 'count' => 0],
    ['value' => 'delivered', 'label' => 'Доставлен', 'count' => 0],
    ['value' => 'cancelled', 'label' => 'Отменен', 'count' => 0],
];

$config = [
    'title' => 'Заказы Ozon',
    'canExport' => true,
    'canSync' => true,
    'canCreateSupply' => false,
    'showSupplies' => false,
    'showFilters' => true,
    'defaultTab' => 'all',
];
@endphp

<x-marketplace.orders-table
    marketplace="ozon"
    :accountId="$accountId"
    :accountName="$accountName ?? 'Ozon'"
    :orders="$orders ?? collect()"
    :statuses="$statuses"
    :config="$config"
/>
@endsection
