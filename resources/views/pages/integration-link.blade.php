@extends('layouts.app')

@section('content')

{{-- BROWSER MODE --}}
<div class="browser-only flex h-screen bg-gray-50" x-data="integrationLinkPage()"
     :class="{
         'flex-row': $store.ui.navPosition === 'left',
         'flex-row-reverse': $store.ui.navPosition === 'right'
     }">
    <template x-if="$store.ui.navPosition === 'left' || $store.ui.navPosition === 'right'">
        <x-sidebar />
    </template>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">RISMENT Integration</h1>
                    <p class="text-sm text-gray-500">Link your account with RISMENT fulfillment platform</p>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">

                    {{-- Status --}}
                    <div class="mb-6">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-3 h-3 rounded-full"
                                 :class="isLinked ? 'bg-green-500' : 'bg-gray-300'"></div>
                            <span class="text-sm font-medium"
                                  :class="isLinked ? 'text-green-700' : 'text-gray-500'"
                                  x-text="isLinked ? 'Connected' : 'Not connected'"></span>
                        </div>
                        <template x-if="linkedAt">
                            <p class="text-xs text-gray-400 ml-6" x-text="'Linked: ' + linkedAt"></p>
                        </template>
                    </div>

                    {{-- Form --}}
                    <form @submit.prevent="saveToken">
                        <div class="mb-4">
                            <label for="link_token" class="block text-sm font-medium text-gray-700 mb-1">
                                Link Token
                            </label>
                            <p class="text-xs text-gray-500 mb-2">
                                Enter the link token provided by RISMENT.
                            </p>
                            <input type="text"
                                   id="link_token"
                                   x-model="linkToken"
                                   placeholder="paste your RISMENT link token here"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-sm font-mono"
                                   :disabled="saving">
                        </div>

                        <template x-if="error">
                            <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-md" x-text="error"></div>
                        </template>

                        <template x-if="successMsg">
                            <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-md" x-text="successMsg"></div>
                        </template>

                        <div class="flex gap-3">
                            <button type="submit"
                                    :disabled="saving || !linkToken"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed">
                                <template x-if="saving">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </template>
                                <span x-text="isLinked ? 'Update Token' : 'Connect'"></span>
                            </button>

                            <template x-if="isLinked">
                                <button type="button"
                                        @click="disconnect"
                                        :disabled="saving"
                                        class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none disabled:opacity-50">
                                    Disconnect
                                </button>
                            </template>
                        </div>
                    </form>
                </div>

                {{-- Info --}}
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-blue-800 mb-2">How it works</h3>
                    <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                        <li>Get a link token from your RISMENT account settings</li>
                        <li>Paste it here and click Connect</li>
                        <li>FBS orders from marketplaces will be sent to RISMENT automatically</li>
                        <li>Stock updates from RISMENT will sync back to SellerMind</li>
                    </ul>
                </div>
            </div>
        </main>
    </div>
</div>

{{-- PWA MODE --}}
<div class="pwa-only min-h-screen bg-gray-50" x-data="integrationLinkPage()">
    <div class="px-4 py-4 bg-white border-b">
        <h1 class="text-lg font-bold text-gray-900">RISMENT Integration</h1>
    </div>

    <div class="p-4">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-3 h-3 rounded-full"
                     :class="isLinked ? 'bg-green-500' : 'bg-gray-300'"></div>
                <span class="text-sm font-medium"
                      :class="isLinked ? 'text-green-700' : 'text-gray-500'"
                      x-text="isLinked ? 'Connected' : 'Not connected'"></span>
            </div>

            <form @submit.prevent="saveToken">
                <input type="text"
                       x-model="linkToken"
                       placeholder="RISMENT link token"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm font-mono mb-3"
                       :disabled="saving">

                <template x-if="error">
                    <div class="mb-3 p-2 bg-red-50 text-red-700 text-xs rounded" x-text="error"></div>
                </template>
                <template x-if="successMsg">
                    <div class="mb-3 p-2 bg-green-50 text-green-700 text-xs rounded" x-text="successMsg"></div>
                </template>

                <div class="flex gap-2">
                    <button type="submit"
                            :disabled="saving || !linkToken"
                            class="flex-1 px-3 py-2 bg-indigo-600 text-white text-sm rounded-md disabled:opacity-50">
                        <span x-text="isLinked ? 'Update' : 'Connect'"></span>
                    </button>
                    <template x-if="isLinked">
                        <button type="button" @click="disconnect" :disabled="saving"
                                class="px-3 py-2 border border-red-300 text-red-700 text-sm rounded-md">
                            Disconnect
                        </button>
                    </template>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function integrationLinkPage() {
    return {
        linkToken: '',
        isLinked: @json(!empty($link) && $link->is_active),
        linkedAt: @json($link?->linked_at?->format('d.m.Y H:i')),
        saving: false,
        error: null,
        successMsg: null,

        async saveToken() {
            this.saving = true;
            this.error = null;
            this.successMsg = null;

            try {
                const token = localStorage.getItem('_x_auth_token') || document.querySelector('meta[name="csrf-token"]')?.content;
                const res = await fetch('/api/integration/link', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Authorization': 'Bearer ' + localStorage.getItem('_x_auth_token'),
                    },
                    body: JSON.stringify({ link_token: this.linkToken }),
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    this.isLinked = true;
                    this.linkedAt = new Date().toLocaleString();
                    this.successMsg = data.message;
                } else {
                    this.error = data.message || 'Failed to save token';
                }
            } catch (e) {
                this.error = 'Network error. Please try again.';
            } finally {
                this.saving = false;
            }
        },

        async disconnect() {
            if (!confirm('Disconnect RISMENT integration?')) return;

            this.saving = true;
            this.error = null;

            try {
                const res = await fetch('/api/integration/link', {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                        'Authorization': 'Bearer ' + localStorage.getItem('_x_auth_token'),
                    },
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    this.isLinked = false;
                    this.linkedAt = null;
                    this.linkToken = '';
                    this.successMsg = data.message;
                } else {
                    this.error = data.message || 'Failed to disconnect';
                }
            } catch (e) {
                this.error = 'Network error.';
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>

@endsection
