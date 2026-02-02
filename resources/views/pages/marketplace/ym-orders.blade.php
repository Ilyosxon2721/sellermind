@extends('layouts.app')

@section('content')
@php
$statuses = [
    ['value' => 'all', 'label' => 'Все', 'count' => 0],
    ['value' => 'processing', 'label' => 'В обработке', 'count' => 0],
    ['value' => 'delivery', 'label' => 'Доставляется', 'count' => 0],
    ['value' => 'delivered', 'label' => 'Доставлен', 'count' => 0],
    ['value' => 'cancelled', 'label' => 'Отменен', 'count' => 0],
];

$config = [
    'title' => 'Заказы Yandex Market',
    'canExport' => true,
    'canSync' => true,
    'canCreateSupply' => false,
    'showSupplies' => false,
    'showFilters' => true,
    'defaultTab' => 'all',
];
@endphp

<x-marketplace.orders-table
    marketplace="ym"
    :accountId="$accountId"
    :accountName="$accountName ?? 'Yandex Market'"
    :orders="$orders ?? collect()"
    :statuses="$statuses"
    :config="$config"
/>
@endsection
