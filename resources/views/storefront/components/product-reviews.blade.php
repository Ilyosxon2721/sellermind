{{-- Блок отзывов на странице товара --}}
@props(['store', 'storeProduct', 'reviews', 'reviewStats'])

@php
    $total = (int) ($reviewStats->total ?? 0);
    $avgRating = $reviewStats->avg_rating ? round((float) $reviewStats->avg_rating, 1) : null;
    $currency = $store->currency ?? 'UZS';
@endphp

<section class="mt-12" x-data="productReviews()" id="reviews">
    {{-- Заголовок --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Отзывы</h2>
            @if($total > 0)
                <div class="flex items-center gap-2 mt-1">
                    <div class="flex items-center">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-5 h-5 {{ $i <= round($avgRating) ? 'text-yellow-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        @endfor
                    </div>
                    <span class="text-sm text-gray-600">{{ $avgRating }} из 5 ({{ $total }} {{ $total === 1 ? 'отзыв' : ($total < 5 ? 'отзыва' : 'отзывов') }})</span>
                </div>
            @else
                <p class="text-sm text-gray-500 mt-1">Пока нет отзывов. Будьте первым!</p>
            @endif
        </div>
        <button @click="showForm = !showForm"
                class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
                :class="showForm ? 'bg-gray-200 text-gray-700' : 'bg-blue-600 text-white hover:bg-blue-700'">
            <span x-text="showForm ? 'Скрыть' : 'Оставить отзыв'"></span>
        </button>
    </div>

    {{-- Форма отзыва --}}
    <div x-show="showForm" x-transition class="mb-8">
        <form @submit.prevent="submitReview" class="bg-gray-50 rounded-xl p-6 space-y-4">
            {{-- Рейтинг --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Оценка</label>
                <div class="flex gap-1">
                    <template x-for="star in [1,2,3,4,5]" :key="star">
                        <button type="button" @click="form.rating = star" class="focus:outline-none">
                            <svg class="w-8 h-8 transition-colors cursor-pointer" :class="star <= form.rating ? 'text-yellow-400' : 'text-gray-300 hover:text-yellow-300'" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </button>
                    </template>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Имя *</label>
                    <input type="text" x-model="form.author_name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" x-model="form.author_email"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Достоинства</label>
                    <textarea x-model="form.pros" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Что понравилось?"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Недостатки</label>
                    <textarea x-model="form.cons" rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Что не понравилось?"></textarea>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Отзыв</label>
                <textarea x-model="form.text" rows="3"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          placeholder="Поделитесь впечатлениями о товаре..."></textarea>
            </div>

            <div class="flex items-center justify-between">
                <p x-show="successMsg" x-text="successMsg" class="text-sm text-green-600 font-medium"></p>
                <p x-show="errorMsg" x-text="errorMsg" class="text-sm text-red-600 font-medium"></p>
                <button type="submit" :disabled="submitting || form.rating === 0"
                        class="px-6 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!submitting">Отправить отзыв</span>
                    <span x-show="submitting">Отправка...</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Список отзывов --}}
    @if($reviews->isNotEmpty())
        <div class="space-y-4">
            @foreach($reviews as $review)
                <div class="bg-white border border-gray-100 rounded-xl p-5 {{ $review->is_featured ? 'ring-2 ring-yellow-200' : '' }}">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-900">{{ $review->author_name }}</span>
                                @if($review->is_featured)
                                    <span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full font-medium">Рекомендует</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1 mt-1">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                @endfor
                            </div>
                        </div>
                        <span class="text-xs text-gray-400">{{ $review->created_at->format('d.m.Y') }}</span>
                    </div>

                    @if($review->pros)
                        <div class="flex items-start gap-2 mb-2">
                            <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <p class="text-sm text-gray-700">{{ $review->pros }}</p>
                        </div>
                    @endif

                    @if($review->cons)
                        <div class="flex items-start gap-2 mb-2">
                            <svg class="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            <p class="text-sm text-gray-700">{{ $review->cons }}</p>
                        </div>
                    @endif

                    @if($review->text)
                        <p class="text-sm text-gray-700 mt-2">{{ $review->text }}</p>
                    @endif

                    @if($review->admin_reply)
                        <div class="mt-3 bg-blue-50 rounded-lg p-3 border-l-3 border-blue-500">
                            <p class="text-xs font-semibold text-blue-700 mb-1">Ответ магазина</p>
                            <p class="text-sm text-gray-700">{{ $review->admin_reply }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        @if($total > 5)
            <div class="text-center mt-6">
                <button @click="loadMoreReviews()"
                        class="px-6 py-2.5 text-sm font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
                        x-text="'Показать все отзывы (' + {{ $total }} + ')'">
                </button>
            </div>
        @endif
    @endif
</section>

<script nonce="{{ $cspNonce ?? '' }}">
function productReviews() {
    return {
        showForm: false,
        submitting: false,
        successMsg: '',
        errorMsg: '',
        form: {
            author_name: '',
            author_email: '',
            rating: 0,
            text: '',
            pros: '',
            cons: '',
        },

        async submitReview() {
            this.submitting = true;
            this.successMsg = '';
            this.errorMsg = '';

            try {
                const response = await fetch('/store/{{ $store->slug }}/api/products/{{ $storeProduct->id }}/reviews', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.form),
                });

                const data = await response.json();

                if (response.ok) {
                    this.successMsg = data.meta?.message || 'Отзыв отправлен на модерацию!';
                    this.form = { author_name: '', author_email: '', rating: 0, text: '', pros: '', cons: '' };
                } else {
                    this.errorMsg = data.message || 'Ошибка при отправке отзыва';
                }
            } catch (e) {
                this.errorMsg = 'Ошибка сети. Попробуйте ещё раз.';
            }

            this.submitting = false;
        },

        async loadMoreReviews() {
            window.location.hash = 'reviews';
            // Подгрузка через API для SPA-like поведения
        }
    }
}
</script>
