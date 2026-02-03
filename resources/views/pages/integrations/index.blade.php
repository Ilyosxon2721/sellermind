@extends('layouts.app')

@section('content')

{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gray-50" x-data="integrationsPage()">
    <x-sidebar></x-sidebar>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ __('integrations.title') }}</h1>
                <p class="text-sm text-gray-500">{{ __('integrations.subtitle') }}</p>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-5xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                    {{-- RISMENT --}}
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                                      :class="rismentConnected ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="rismentConnected ? 'bg-green-500' : 'bg-gray-400'"></span>
                                    <span x-text="rismentConnected ? '{{ __('integrations.status_connected') }}' : '{{ __('integrations.status_not_connected') }}'"></span>
                                </span>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ __('integrations.risment_title') }}</h3>
                            <p class="text-sm text-gray-500 mb-5">{{ __('integrations.risment_description') }}</p>
                            <a href="/integrations/risment"
                               class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium rounded-lg transition"
                               :class="rismentConnected
                                   ? 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                                   : 'bg-indigo-600 text-white hover:bg-indigo-700'">
                                <span x-text="rismentConnected ? '{{ __('integrations.configure') }}' : '{{ __('integrations.connect') }}'"></span>
                            </a>
                        </div>
                    </div>

                    {{-- 1C --}}
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm opacity-70 overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-12 h-12 rounded-xl bg-yellow-100 flex items-center justify-center flex-shrink-0">
                                    <span class="text-lg font-bold text-yellow-700">1С</span>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                    {{ __('integrations.status_coming_soon') }}
                                </span>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ __('integrations.onec_title') }}</h3>
                            <p class="text-sm text-gray-500 mb-5">{{ __('integrations.onec_description') }}</p>
                            <button disabled
                                    class="w-full px-4 py-2.5 text-sm font-medium rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed">
                                {{ __('integrations.status_coming_soon') }}
                            </button>
                        </div>
                    </div>

                    {{-- Мой Склад --}}
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm opacity-70 overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                                    </svg>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                    {{ __('integrations.status_coming_soon') }}
                                </span>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ __('integrations.moysklad_title') }}</h3>
                            <p class="text-sm text-gray-500 mb-5">{{ __('integrations.moysklad_description') }}</p>
                            <button disabled
                                    class="w-full px-4 py-2.5 text-sm font-medium rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed">
                                {{ __('integrations.status_coming_soon') }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen bg-gray-50 pb-20" x-data="integrationsPage()">
    <div class="px-4 py-4 bg-white border-b">
        <h1 class="text-lg font-bold text-gray-900">{{ __('integrations.title') }}</h1>
        <p class="text-xs text-gray-500">{{ __('integrations.subtitle') }}</p>
    </div>

    <div class="p-4 space-y-4">
        {{-- RISMENT --}}
        <a href="/integrations/risment" class="block bg-white rounded-xl border border-gray-200 shadow-sm p-4">
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('integrations.risment_title') }}</h3>
                    <p class="text-xs text-gray-500 truncate">{{ __('integrations.risment_description') }}</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="w-2 h-2 rounded-full" :class="rismentConnected ? 'bg-green-500' : 'bg-gray-300'"></span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </div>
            </div>
        </a>

        {{-- 1C --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 opacity-70">
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-yellow-100 flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-bold text-yellow-700">1С</span>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('integrations.onec_title') }}</h3>
                    <p class="text-xs text-gray-500 truncate">{{ __('integrations.onec_description') }}</p>
                </div>
                <span class="text-xs font-medium text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full flex-shrink-0">
                    {{ __('integrations.status_coming_soon') }}
                </span>
            </div>
        </div>

        {{-- Мой Склад --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 opacity-70">
            <div class="flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('integrations.moysklad_title') }}</h3>
                    <p class="text-xs text-gray-500 truncate">{{ __('integrations.moysklad_description') }}</p>
                </div>
                <span class="text-xs font-medium text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full flex-shrink-0">
                    {{ __('integrations.status_coming_soon') }}
                </span>
            </div>
        </div>
    </div>
</div>

<script>
function integrationsPage() {
    return {
        rismentConnected: @json(!empty($rismentLink)),
    };
}
</script>

@endsection
