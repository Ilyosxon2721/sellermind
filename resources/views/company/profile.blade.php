@extends('layouts.app')

@section('content')
<div class="flex h-screen bg-gray-50" x-data="companyProfilePage()">
    <x-sidebar />

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Профиль компании</h1>
                    <p class="text-sm text-gray-500 mt-1">Управление компанией, сотрудниками и подпиской</p>
                </div>
            </div>
        </header>

        <!-- Tabs Navigation -->
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6">
            <nav class="flex space-x-6 overflow-x-auto">
                <button
                    @click="activeTab = 'companies'"
                    class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition"
                    :class="activeTab === 'companies' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                    Компании
                </button>
                <button
                    @click="activeTab = 'employees'"
                    class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition"
                    :class="activeTab === 'employees' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                    Сотрудники
                </button>
                <button
                    @click="activeTab = 'access'"
                    class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition"
                    :class="activeTab === 'access' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                    Доступы
                </button>
                <button
                    @click="activeTab = 'billing'"
                    class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition"
                    :class="activeTab === 'billing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                    Биллинг
                </button>
            </nav>
        </div>

        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            <!-- Companies Tab -->
            <div x-show="activeTab === 'companies'">
                @include('company.tabs.companies')
            </div>

            <!-- Employees Tab -->
            <div x-show="activeTab === 'employees'">
                @include('company.tabs.employees')
            </div>

            <!-- Access Tab -->
            <div x-show="activeTab === 'access'">
                @include('company.tabs.access')
            </div>

            <!-- Billing Tab -->
            <div x-show="activeTab === 'billing'">
                @include('company.tabs.billing')
            </div>
        </main>
    </div>
</div>

<script>
function companyProfilePage() {
    return {
        activeTab: 'companies',

        init() {
            // Get tab from URL hash if present
            const hash = window.location.hash.substring(1);
            if (['companies', 'employees', 'access', 'billing'].includes(hash)) {
                this.activeTab = hash;
            }
        }
    };
}
</script>
@endsection
