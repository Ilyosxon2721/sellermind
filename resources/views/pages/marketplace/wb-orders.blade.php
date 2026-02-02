@extends('layouts.app')

@section('content')
@php
$statuses = [
    ['value' => 'all', 'label' => 'Все', 'count' => 0],
    ['value' => 'new', 'label' => 'Новые', 'count' => 0],
    ['value' => 'in_assembly', 'label' => 'На сборке', 'count' => 0],
    ['value' => 'in_delivery', 'label' => 'В доставке', 'count' => 0],
    ['value' => 'completed', 'label' => 'Архив', 'count' => 0],
    ['value' => 'cancelled', 'label' => 'Отменены', 'count' => 0],
];

$config = [
    'title' => 'Заказы Wildberries',
    'canExport' => true,
    'canSync' => true,
    'canCreateSupply' => true,
    'showSupplies' => true,
    'showFilters' => true,
    'defaultTab' => 'new',
];
@endphp

<x-marketplace.orders-table
    marketplace="wb"
    :accountId="$accountId"
    :accountName="$accountName ?? 'Wildberries'"
    :orders="$orders ?? collect()"
    :statuses="$statuses"
    :config="$config"
/>
@endsection
