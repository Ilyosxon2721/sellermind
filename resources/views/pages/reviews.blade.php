@extends('layouts.app')

@section('content')
<div x-data="reviewsPage()" x-init="init()" class="flex h-screen bg-gray-50">

    <x-sidebar></x-sidebar>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Отзывы</h1>
                    <p class="text-sm text-gray-500">Управление отзывами и автоответы с ИИ</p>
                </div>
                <div class="flex items-center space-x-3">
                    <button @click="showTemplatesModal = true"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50">
                        📝 Шаблоны
                    </button>
                    <button @click="showStatsModal = true"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50">
                        📊 Статистика
                    </button>
                    <button @click="loadReviews()"
                            :disabled="loading"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-50">
                        <span x-show="!loading">🔄 Обновить</span>
                        <span x-show="loading">Загрузка...</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Filters -->
        <div class="bg-white border-b border-gray-200 px-6 py-3">
            <div class="flex items-center space-x-4">
                <select x-model="filters.status" @change="loadReviews()"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">Все статусы</option>
                    <option value="pending">Ожидают ответа</option>
                    <option value="responded">Отвечено</option>
                    <option value="ignored">Игнорируются</option>
                </select>

                <select x-model="filters.rating" @change="loadReviews()"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">Все оценки</option>
                    <option value="5">⭐⭐⭐⭐⭐ (5)</option>
                    <option value="4">⭐⭐⭐⭐ (4)</option>
                    <option value="3">⭐⭐⭐ (3)</option>
                    <option value="2">⭐⭐ (2)</option>
                    <option value="1">⭐ (1)</option>
                </select>

                <select x-model="filters.sentiment" @change="loadReviews()"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">Все настроения</option>
                    <option value="positive">Позитивные</option>
                    <option value="neutral">Нейтральные</option>
                    <option value="negative">Негативные</option>
                </select>

                <select x-model="filters.marketplace" @change="loadReviews()"
                        class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">Все маркетплейсы</option>
                    <option value="wildberries">Wildberries</option>
                    <option value="ozon">Ozon</option>
                    <option value="yandex">Yandex Market</option>
                </select>

                <div class="flex-1"></div>

                <button @click="bulkGenerateResponses()"
                        :disabled="selectedReviews.length === 0 || bulkGenerating"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!bulkGenerating">🤖 Массовая генерация (<span x-text="selectedReviews.length"></span>)</span>
                    <span x-show="bulkGenerating">Генерация...</span>
                </button>
            </div>
        </div>

        <!-- Content -->
        <main class="flex-1 overflow-y-auto p-6">
            <!-- Loading -->
            <div x-show="loading && reviews.length === 0" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                <p class="mt-2 text-gray-600">Загрузка отзывов...</p>
            </div>

            <!-- Reviews List -->
            <div x-show="!loading || reviews.length > 0" class="space-y-4">
                <template x-for="review in reviews" :key="review.id">
                    <div class="bg-white rounded-lg shadow border border-gray-200 p-6">
                        <!-- Review Header -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-start space-x-4">
                                <input type="checkbox"
                                       :checked="selectedReviews.includes(review.id)"
                                       @change="toggleReviewSelection(review.id)"
                                       class="mt-1 h-4 w-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <span class="font-semibold text-gray-900" x-text="review.customer_name || 'Аноним'"></span>
                                        <span class="text-sm text-gray-500" x-text="'• ' + formatDate(review.created_at)"></span>
                                    </div>
                                    <div class="flex items-center space-x-2 mb-2">
                                        <div x-html="renderStars(review.rating)"></div>
                                        <span :class="getSentimentBadgeClass(review.sentiment)"
                                              class="px-2 py-0.5 text-xs rounded-full font-medium"
                                              x-text="getSentimentLabel(review.sentiment)"></span>
                                        <span class="px-2 py-0.5 text-xs bg-gray-100 text-gray-700 rounded-full font-medium capitalize"
                                              x-text="review.marketplace"></span>
                                    </div>
                                    <p class="text-gray-700 text-sm mb-2" x-text="review.review_text"></p>
                                    <div x-show="review.product" class="text-sm text-gray-500">
                                        Товар: <span x-text="review.product?.name"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Response Section -->
                        <div class="border-t border-gray-200 pt-4 mt-4">
                            <div x-show="!review.response_text && !editingReview[review.id]">
                                <div class="flex items-center space-x-3">
                                    <button @click="generateResponse(review.id)"
                                            :disabled="generatingResponse === review.id"
                                            class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                                        <span x-show="generatingResponse !== review.id">🤖 Сгенерировать ответ</span>
                                        <span x-show="generatingResponse === review.id">Генерация...</span>
                                    </button>
                                    <button @click="showTemplatesForReview(review.id)"
                                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                                        📝 Использовать шаблон
                                    </button>
                                    <button @click="startEditingResponse(review.id)"
                                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                                        ✍️ Написать вручную
                                    </button>
                                </div>
                            </div>

                            <!-- Edit Response -->
                            <div x-show="editingReview[review.id]">
                                <textarea x-model="editingResponse[review.id]"
                                          rows="4"
                                          placeholder="Введите ваш ответ на отзыв..."
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500"></textarea>
                                <div class="flex items-center justify-between mt-3">
                                    <div class="flex items-center space-x-2">
                                        <button @click="saveResponse(review.id, false)"
                                                :disabled="!editingResponse[review.id]"
                                                class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                                            💾 Сохранить
                                        </button>
                                        <button @click="cancelEditing(review.id)"
                                                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                                            Отмена
                                        </button>
                                    </div>
                                    <label class="flex items-center text-sm text-gray-600">
                                        <input type="checkbox"
                                               x-model="isAiGenerated[review.id]"
                                               class="mr-2 h-4 w-4 text-indigo-600 rounded border-gray-300">
                                        Отметить как AI-сгенерированный
                                    </label>
                                </div>
                            </div>

                            <!-- Saved Response -->
                            <div x-show="review.response_text && !editingReview[review.id]">
                                <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-semibold text-indigo-900">Ваш ответ:</span>
                                        <div class="flex items-center space-x-2">
                                            <span x-show="review.is_ai_generated"
                                                  class="px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full font-medium">
                                                🤖 AI
                                            </span>
                                            <button @click="startEditingResponse(review.id, review.response_text)"
                                                    class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                                Редактировать
                                            </button>
                                        </div>
                                    </div>
                                    <p class="text-gray-700 text-sm" x-text="review.response_text"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div x-show="reviews.length === 0 && !loading" class="text-center py-12">
                    <p class="text-gray-500">Отзывов не найдено</p>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Templates Modal -->
<div x-show="showTemplatesModal"
     x-cloak
     @click.self="showTemplatesModal = false"
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">Шаблоны ответов</h2>
            <button @click="showTemplatesModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[70vh]">
            <div class="space-y-4">
                <template x-for="template in templates" :key="template.id">
                    <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 cursor-pointer"
                         @click="useTemplate(template)">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-semibold text-gray-900" x-text="template.name"></span>
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded-full"
                                  x-text="getCategoryLabel(template.category)"></span>
                        </div>
                        <p class="text-sm text-gray-700" x-text="template.template_text"></p>
                        <div x-show="template.usage_count > 0" class="mt-2 text-xs text-gray-500">
                            Использован <span x-text="template.usage_count"></span> раз
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Modal -->
<div x-show="showStatsModal"
     x-cloak
     @click.self="showStatsModal = false"
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-xl font-bold text-gray-900">Статистика отзывов</h2>
            <button @click="showStatsModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="text-sm text-blue-600 mb-1">Всего отзывов</div>
                    <div class="text-2xl font-bold text-blue-900" x-text="stats.total_reviews || 0"></div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="text-sm text-green-600 mb-1">Обработано</div>
                    <div class="text-2xl font-bold text-green-900" x-text="stats.responded_count || 0"></div>
                </div>
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <div class="text-sm text-purple-600 mb-1">AI-ответов</div>
                    <div class="text-2xl font-bold text-purple-900" x-text="stats.ai_responses_count || 0"></div>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <div class="text-sm text-amber-600 mb-1">Средняя оценка</div>
                    <div class="text-2xl font-bold text-amber-900" x-text="(stats.average_rating || 0).toFixed(1)"></div>
                </div>
            </div>
            <div class="space-y-3">
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-gray-600">Процент ответов</span>
                        <span class="font-semibold text-gray-900" x-text="(stats.response_rate || 0).toFixed(1) + '%'"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-indigo-600 h-2 rounded-full" :style="`width: ${stats.response_rate || 0}%`"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }
</style>

<script>
function reviewsPage() {
    return {
        reviews: [],
        templates: [],
        stats: {},
        loading: false,
        generatingResponse: null,
        bulkGenerating: false,
        selectedReviews: [],
        editingReview: {},
        editingResponse: {},
        isAiGenerated: {},
        showTemplatesModal: false,
        showStatsModal: false,
        filters: {
            status: 'pending',
            rating: '',
            sentiment: '',
            marketplace: '',
        },

        async init() {
            await Promise.all([
                this.loadReviews(),
                this.loadTemplates(),
                this.loadStats()
            ]);
        },

        async loadReviews() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([key, value]) => {
                    if (value) params.append(key, value);
                });

                const response = await fetch(`/api/reviews?${params}`, {
                    headers: {
                        'Authorization': `Bearer ${window.api.getToken()}`,
                    },
                });

                const data = await response.json();
                this.reviews = data.data || [];
            } catch (error) {
                console.error('Failed to load reviews:', error);
                alert('Ошибка загрузки отзывов');
            } finally {
                this.loading = false;
            }
        },

        async loadTemplates() {
            try {
                const response = await fetch('/api/reviews/templates', {
                    headers: {
                        'Authorization': `Bearer ${window.api.getToken()}`,
                    },
                });

                const data = await response.json();
                this.templates = data.data || [];
            } catch (error) {
                console.error('Failed to load templates:', error);
            }
        },

        async loadStats() {
            try {
                const response = await fetch('/api/reviews/statistics', {
                    headers: {
                        'Authorization': `Bearer ${window.api.getToken()}`,
                    },
                });

                this.stats = await response.json();
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        async generateResponse(reviewId) {
            this.generatingResponse = reviewId;
            try {
                const response = await fetch(`/api/reviews/${reviewId}/generate`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${window.api.getToken()}`,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        tone: 'professional',
                        length: 'medium',
                        language: 'ru',
                    }),
                });

                const data = await response.json();

                if (data.response) {
                    this.editingReview[reviewId] = true;
                    this.editingResponse[reviewId] = data.response;
                    this.isAiGenerated[reviewId] = true;
                }
            } catch (error) {
                console.error('Failed to generate response:', error);
                alert('Ошибка генерации ответа');
            } finally {
                this.generatingResponse = null;
            }
        },

        async saveResponse(reviewId, isAiGenerated = false) {
            try {
                const response = await fetch(`/api/reviews/${reviewId}/save-response`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${window.api.getToken()}`,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        response_text: this.editingResponse[reviewId],
                        is_ai_generated: this.isAiGenerated[reviewId] || isAiGenerated,
                    }),
                });

                const data = await response.json();

                // Update review in list
                const reviewIndex = this.reviews.findIndex(r => r.id === reviewId);
                if (reviewIndex !== -1) {
                    this.reviews[reviewIndex] = data.data;
                }

                this.editingReview[reviewId] = false;
                delete this.editingResponse[reviewId];
                delete this.isAiGenerated[reviewId];

                await this.loadStats();
            } catch (error) {
                console.error('Failed to save response:', error);
                alert('Ошибка сохранения ответа');
            }
        },

        async bulkGenerateResponses() {
            if (this.selectedReviews.length === 0) return;

            this.bulkGenerating = true;
            try {
                const response = await fetch('/api/reviews/bulk-generate', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${window.api.getToken()}`,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        review_ids: this.selectedReviews,
                        save_immediately: false,
                    }),
                });

                const data = await response.json();

                // Update reviews with generated responses
                data.results.forEach(result => {
                    if (result.success) {
                        this.editingReview[result.review_id] = true;
                        this.editingResponse[result.review_id] = result.response;
                        this.isAiGenerated[result.review_id] = true;
                    }
                });

                alert(`Сгенерировано ${data.success_count} из ${data.total} ответов`);
            } catch (error) {
                console.error('Failed to bulk generate:', error);
                alert('Ошибка массовой генерации');
            } finally {
                this.bulkGenerating = false;
            }
        },

        startEditingResponse(reviewId, existingResponse = '') {
            this.editingReview[reviewId] = true;
            this.editingResponse[reviewId] = existingResponse;
            this.isAiGenerated[reviewId] = false;
        },

        cancelEditing(reviewId) {
            this.editingReview[reviewId] = false;
            delete this.editingResponse[reviewId];
            delete this.isAiGenerated[reviewId];
        },

        toggleReviewSelection(reviewId) {
            const index = this.selectedReviews.indexOf(reviewId);
            if (index > -1) {
                this.selectedReviews.splice(index, 1);
            } else {
                this.selectedReviews.push(reviewId);
            }
        },

        async showTemplatesForReview(reviewId) {
            this.showTemplatesModal = true;
            this.currentReviewForTemplate = reviewId;
        },

        useTemplate(template) {
            if (this.currentReviewForTemplate) {
                this.editingReview[this.currentReviewForTemplate] = true;
                this.editingResponse[this.currentReviewForTemplate] = template.template_text;
                this.isAiGenerated[this.currentReviewForTemplate] = false;
                this.showTemplatesModal = false;
            }
        },

        renderStars(rating) {
            return '⭐'.repeat(rating) + '☆'.repeat(5 - rating);
        },

        getSentimentBadgeClass(sentiment) {
            const classes = {
                positive: 'bg-green-100 text-green-700',
                neutral: 'bg-gray-100 text-gray-700',
                negative: 'bg-red-100 text-red-700',
            };
            return classes[sentiment] || 'bg-gray-100 text-gray-700';
        },

        getSentimentLabel(sentiment) {
            const labels = {
                positive: 'Позитивный',
                neutral: 'Нейтральный',
                negative: 'Негативный',
            };
            return labels[sentiment] || sentiment;
        },

        getCategoryLabel(category) {
            const labels = {
                positive: 'Позитивный',
                negative_quality: 'Негатив: Качество',
                negative_delivery: 'Негатив: Доставка',
                negative_size: 'Негатив: Размер',
                neutral: 'Нейтральный',
                question: 'Вопрос',
                complaint: 'Жалоба',
            };
            return labels[category] || category;
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            });
        },
    };
}
</script>
@endsection
