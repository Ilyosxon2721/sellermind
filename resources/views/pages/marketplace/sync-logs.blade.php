@extends('layouts.app')

@section('content')
<div class="flex h-screen bg-gray-50">

    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Журнал интеграций</h1>
                    <p class="text-gray-500 text-sm mt-1">Логи синхронизации маркетплейсов</p>
                </div>
                <a href="{{ route('marketplace.index') }}" class="btn btn-secondary inline-flex items-center text-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Dashboard
                </a>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            {{-- Filters --}}
            <div class="card mb-6">
                <div class="card-body">
                    <form method="get" action="{{ route('marketplace.sync-logs') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="form-label">Маркетплейс</label>
                            <select name="marketplace" class="form-select">
                                <option value="">Все</option>
                                @foreach($marketplaces as $mp)
                                    <option value="{{ $mp }}" @selected($filters['marketplace'] === $mp)>
                                        {{ match($mp) {
                                            'wb' => 'Wildberries',
                                            'ozon' => 'Ozon',
                                            'uzum' => 'Uzum Market',
                                            'ym' => 'Yandex Market',
                                            default => $mp
                                        } }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Аккаунт</label>
                            <select name="account_id" class="form-select">
                                <option value="">Все</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}" @selected($filters['account_id'] == $acc->id)>
                                        {{ $acc->name }} ({{ $acc->marketplace }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Тип</label>
                            <select name="type" class="form-select">
                                <option value="">Все</option>
                                <option value="orders" @selected($filters['type'] === 'orders')>Заказы</option>
                                <option value="products" @selected($filters['type'] === 'products')>Товары</option>
                                <option value="stocks" @selected($filters['type'] === 'stocks')>Остатки</option>
                                <option value="prices" @selected($filters['type'] === 'prices')>Цены</option>
                                <option value="reports" @selected($filters['type'] === 'reports')>Отчёты</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Статус</label>
                            <select name="status" class="form-select">
                                <option value="">Все</option>
                                <option value="pending" @selected($filters['status'] === 'pending')>Ожидает</option>
                                <option value="running" @selected($filters['status'] === 'running')>В процессе</option>
                                <option value="success" @selected($filters['status'] === 'success')>Успешно</option>
                                <option value="error" @selected($filters['status'] === 'error')>Ошибка</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2">
                            <button type="submit" class="btn btn-primary flex-1 sm:flex-none text-sm">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                Фильтр
                            </button>
                            <a href="{{ route('marketplace.sync-logs') }}" class="btn btn-ghost text-sm">
                                Сброс
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Stats --}}
            <div class="mb-4 text-sm text-gray-500">
                Всего записей: <span class="font-medium text-gray-900">{{ $logs->total() }}</span>
            </div>

            {{-- Logs table --}}
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="hidden sm:table-cell">ID</th>
                            <th>Дата</th>
                            <th>Маркетплейс</th>
                            <th class="hidden md:table-cell">Аккаунт</th>
                            <th class="hidden lg:table-cell">Тип</th>
                            <th>Статус</th>
                            <th class="hidden sm:table-cell">Время</th>
                            <th class="hidden lg:table-cell">Сообщение</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="hidden sm:table-cell text-gray-500">{{ $log->id }}</td>
                                <td class="whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $log->created_at->format('d.m.Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->created_at->format('H:i:s') }}</div>
                                </td>
                                <td>
                                    @php
                                        $mpLabel = match($log->account?->marketplace) {
                                            'wb' => 'WB',
                                            'ozon' => 'Ozon',
                                            'uzum' => 'Uzum',
                                            'ym' => 'YM',
                                            default => $log->account?->marketplace ?? '—'
                                        };
                                        $mpClass = match($log->account?->marketplace) {
                                            'wb' => 'badge-wb',
                                            'ozon' => 'badge-ozon',
                                            'uzum' => 'badge-uzum',
                                            'ym' => 'badge-ym',
                                            default => 'badge-gray'
                                        };
                                    @endphp
                                    <span class="badge {{ $mpClass }}">{{ $mpLabel }}</span>
                                </td>
                                <td class="hidden md:table-cell">{{ $log->account?->name ?? '—' }}</td>
                                <td class="hidden lg:table-cell">
                                    <span class="badge badge-gray">{{ $log->getTypeLabel() }}</span>
                                </td>
                                <td>
                                    @php
                                        $statusClass = match($log->status) {
                                            'success' => 'badge-success',
                                            'error' => 'badge-danger',
                                            'running' => 'badge-primary',
                                            'pending' => 'badge-warning',
                                            default => 'badge-gray'
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ $log->getStatusLabel() }}</span>
                                </td>
                                <td class="hidden sm:table-cell text-gray-500 text-sm whitespace-nowrap">
                                    @if($duration = $log->getDuration())
                                        {{ $duration }}s
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="hidden lg:table-cell text-gray-600 text-sm max-w-xs">
                                    <span class="truncate block" title="{{ $log->message }}">
                                        {{ \Illuminate\Support\Str::limit($log->message, 50) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-12">
                                    <div class="empty-state">
                                        <svg class="empty-state-icon mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                        <p class="empty-state-title">Записей пока нет</p>
                                        <p class="empty-state-text">Логи синхронизации появятся после подключения маркетплейсов</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if($logs->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                        {{ $logs->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </main>
    </div>
</div>
@endsection
