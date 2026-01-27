<!-- Company Prompt Modal -->
<div x-data="companyPromptModal()"
     x-show="$store.auth.showCompanyPrompt && $store.auth.isAuthenticated"
     x-cloak
     @keydown.escape.window="$store.auth.showCompanyPrompt && !saving && ($store.auth.showCompanyPrompt = false)"
     class="fixed inset-0 z-50 overflow-y-auto"
     role="dialog"
     aria-modal="true"
     aria-labelledby="company-modal-title"
     style="display: none;">

    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity"
         @click="!saving && ($store.auth.showCompanyPrompt = false)"
         aria-hidden="true"></div>

    <!-- Modal -->
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="relative w-full max-w-md transform rounded-2xl bg-white p-8 shadow-2xl transition-all"
             @click.stop>

            <!-- Close button - только если не сохраняем -->
            <button v-show="!saving"
                    @click="$store.auth.showCompanyPrompt = false"
                    class="absolute right-4 top-4 text-gray-400 hover:text-gray-600 transition-colors"
                    aria-label="<?php echo e(__('admin.close')); ?>">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <!-- Icon -->
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 mb-4" aria-hidden="true">
                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>

            <!-- Content -->
            <div class="text-center mb-6">
                <h3 id="company-modal-title" class="text-2xl font-bold text-gray-900 mb-2"><?php echo e(__('admin.create_company')); ?></h3>
                <p class="text-gray-600">
                    <?php echo e(__('admin.create_company_desc')); ?>

                </p>
            </div>

            <!-- Form -->
            <form @submit.prevent="createCompany" class="space-y-4">
                <!-- Company Name -->
                <div>
                    <label for="company-name" class="block text-sm font-medium text-gray-700 mb-1">
                        <?php echo e(__('admin.company_name')); ?> <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="company-name"
                        x-model="form.name"
                        :disabled="saving"
                        required
                        maxlength="255"
                        placeholder="<?php echo e(__('admin.company_placeholder')); ?>"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed transition-colors"
                        :class="{'border-red-500': errors.name}">
                    <p x-show="errors.name" x-text="errors.name" class="mt-1 text-sm text-red-600"></p>
                </div>


                <!-- Error Message -->
                <div x-show="errorMessage" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-start">
                        <svg class="h-5 w-5 text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <p x-text="errorMessage" class="ml-3 text-sm text-red-700"></p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-2">
                    <button
                        type="button"
                        @click="$store.auth.showCompanyPrompt = false"
                        :disabled="saving"
                        class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <?php echo e(__('admin.later')); ?>

                    </button>
                    <button
                        type="submit"
                        :disabled="saving || !form.name"
                        class="flex-1 px-4 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center justify-center">
                        <svg x-show="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="saving ? '<?php echo e(__('admin.creating')); ?>' : '<?php echo e(__('admin.create')); ?>'"></span>
                    </button>
                </div>
            </form>

            <!-- Help Text -->
            <p class="mt-4 text-xs text-center text-gray-500">
                <?php echo e(__('admin.additional_info_later')); ?>

            </p>
        </div>
    </div>
</div>

<script>
function companyPromptModal() {
    return {
        form: {
            name: ''
        },
        errors: {},
        errorMessage: '',
        saving: false,

        async createCompany() {
            this.errors = {};
            this.errorMessage = '';

            // Validation
            if (!this.form.name || this.form.name.trim() === '') {
                this.errors.name = '<?php echo e(__('admin.company_name_required')); ?>';
                return;
            }

            this.saving = true;

            try {
                const result = await window.api.companies.create({
                    name: this.form.name.trim()
                });

                // Update user data if returned
                if (result.user) {
                    Alpine.store('auth').user = result.user;
                }

                // Reload companies
                await Alpine.store('auth').loadCompanies();

                // Reset form
                this.form = { name: '' };

                // Show success toast
                this.showToast('success', '<?php echo e(__('admin.company_created')); ?>');

                // Close modal
                Alpine.store('auth').showCompanyPrompt = false;

            } catch (error) {
                console.error('Failed to create company:', error);

                if (error.response?.data?.errors) {
                    this.errors = error.response.data.errors;
                } else if (error.response?.data?.message) {
                    this.errorMessage = error.response.data.message;
                } else {
                    this.errorMessage = '<?php echo e(__('admin.company_create_error')); ?>';
                }
            } finally {
                this.saving = false;
            }
        },

        showToast(type, message) {
            const toast = document.createElement('div');
            toast.className = `flex items-center gap-3 px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 ${
                type === 'success' ? 'bg-green-500' : 'bg-red-500'
            } text-white`;

            toast.innerHTML = `
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${type === 'success'
                        ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>'
                        : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>'
                    }
                </svg>
                <span class="font-medium">${message}</span>
            `;

            const container = document.getElementById('toast-container');
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('opacity-0', 'translate-x-4');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    };
}
</script>
<?php /**PATH D:\server\OSPanel\home\sellermind\resources\views\components\company-prompt-modal.blade.php ENDPATH**/ ?>