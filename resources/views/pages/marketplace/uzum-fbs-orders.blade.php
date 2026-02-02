@extends('layouts.app')

@section('content')
@php
$statuses = [
    ['value' => 'all', 'label' => 'Все', 'count' => 0],
    ['value' => 'new', 'label' => 'Новые', 'count' => 0],
    ['value' => 'in_assembly', 'label' => 'В сборке', 'count' => 0],
    ['value' => 'in_supply', 'label' => 'В поставке', 'count' => 0],
    ['value' => 'accepted_uzum', 'label' => 'Приняты Uzum', 'count' => 0],
    ['value' => 'waiting_pickup', 'label' => 'Ждут выдачи', 'count' => 0],
    ['value' => 'issued', 'label' => 'Выданы', 'count' => 0],
    ['value' => 'cancelled', 'label' => 'Отменены', 'count' => 0],
    ['value' => 'returns', 'label' => 'Возвраты', 'count' => 0],
];

$config = [
    'title' => 'Заказы Uzum Market',
    'canExport' => true,
    'canSync' => true,
    'canCreateSupply' => false,
    'showSupplies' => false,
    'showFilters' => true,
    'defaultTab' => 'new',
];
@endphp

<x-marketplace.orders-table
    marketplace="uzum"
    :accountId="$accountId"
    :accountName="$accountName ?? 'Uzum Market'"
    :orders="$orders ?? collect()"
    :statuses="$statuses"
    :config="$config"
/>
@endsection
