<?php

namespace Database\Seeders;

use App\Models\ReviewTemplate;
use Illuminate\Database\Seeder;

class ReviewTemplatesSeeder extends Seeder
{
    /**
     * Seed the review response templates.
     */
    public function run(): void
    {
        $templates = [
            // Positive Reviews
            [
                'name' => 'Благодарность за позитивный отзыв',
                'category' => 'positive',
                'template_text' => 'Здравствуйте, {customer_name}! Благодарим за ваш отзыв и высокую оценку! Мы очень рады, что вам понравился {product_name}. Ваше мнение очень важно для нас. Надеемся на дальнейшее сотрудничество!',
                'is_system' => true,
                'rating_min' => 4,
                'rating_max' => 5,
            ],
            [
                'name' => 'Краткая благодарность (5 звезд)',
                'category' => 'positive',
                'template_text' => 'Спасибо за 5 звезд, {customer_name}! Рады, что вы остались довольны покупкой. Ждем вас снова!',
                'is_system' => true,
                'rating_min' => 5,
                'rating_max' => 5,
            ],
            [
                'name' => 'Благодарность с приглашением',
                'category' => 'positive',
                'template_text' => 'Благодарим за отличный отзыв! Очень приятно, что {product_name} оправдал ваши ожидания. Следите за нашими новинками и акциями!',
                'is_system' => true,
                'rating_min' => 4,
                'rating_max' => 5,
            ],

            // Negative - Quality Issues
            [
                'name' => 'Извинения за проблему с качеством',
                'category' => 'negative_quality',
                'template_text' => 'Здравствуйте, {customer_name}! Приносим свои извинения за возникшую проблему с {product_name}. Это не соответствует нашим стандартам качества. Пожалуйста, свяжитесь с нами напрямую, чтобы мы могли решить этот вопрос и предложить замену или возврат.',
                'is_system' => true,
                'rating_min' => 1,
                'rating_max' => 2,
                'keywords' => ['брак', 'дефект', 'качество', 'сломан', 'поврежден', 'не работает'],
            ],
            [
                'name' => 'Извинения и предложение решения',
                'category' => 'negative_quality',
                'template_text' => 'Примите наши извинения за неудобства! Мы серьезно относимся к контролю качества. Свяжитесь с нами для замены товара или полного возврата средств. Ваша удовлетворенность для нас важнее всего.',
                'is_system' => true,
                'rating_min' => 1,
                'rating_max' => 3,
                'keywords' => ['плохое качество', 'не соответствует', 'разочарован'],
            ],

            // Negative - Delivery Issues
            [
                'name' => 'Извинения за задержку доставки',
                'category' => 'negative_delivery',
                'template_text' => 'Здравствуйте, {customer_name}! Приносим извинения за задержку в доставке {product_name}. Мы понимаем ваше разочарование и работаем над улучшением логистики. Надеемся, что товар оправдает ваши ожидания.',
                'is_system' => true,
                'rating_min' => 1,
                'rating_max' => 3,
                'keywords' => ['доставка', 'долго', 'опоздал', 'не пришел', 'задержка'],
            ],
            [
                'name' => 'Извинения за проблему с упаковкой',
                'category' => 'negative_delivery',
                'template_text' => 'Извините за ненадлежащую упаковку! Мы пересмотрим наши стандарты упаковки, чтобы такого больше не повторялось. Если товар поврежден, свяжитесь с нами для замены.',
                'is_system' => true,
                'rating_min' => 1,
                'rating_max' => 3,
                'keywords' => ['упаковка', 'помято', 'порвано', 'сломано при доставке'],
            ],

            // Negative - Size/Fit Issues
            [
                'name' => 'Помощь с размером',
                'category' => 'negative_size',
                'template_text' => 'Здравствуйте! Жаль, что размер не подошел. Рекомендуем ознакомиться с нашей таблицей размеров перед следующим заказом. Мы всегда готовы помочь с обменом на другой размер.',
                'is_system' => true,
                'rating_min' => 1,
                'rating_max' => 3,
                'keywords' => ['размер', 'маломерит', 'большемерит', 'не подошло', 'не тот размер'],
            ],

            // Neutral Reviews
            [
                'name' => 'Ответ на нейтральный отзыв',
                'category' => 'neutral',
                'template_text' => 'Благодарим за ваш отзыв, {customer_name}! Мы ценим вашу обратную связь и постоянно работаем над улучшением {product_name}. Если у вас есть конкретные предложения, будем рады их услышать.',
                'is_system' => true,
                'rating_min' => 3,
                'rating_max' => 3,
            ],
            [
                'name' => 'Ответ на среднюю оценку',
                'category' => 'neutral',
                'template_text' => 'Спасибо за оценку! Нам важно знать, что можно улучшить. Пожалуйста, напишите нам, если есть какие-то конкретные замечания по {product_name}.',
                'is_system' => true,
                'rating_min' => 3,
                'rating_max' => 4,
            ],

            // Questions
            [
                'name' => 'Ответ на вопрос',
                'category' => 'question',
                'template_text' => 'Здравствуйте, {customer_name}! Спасибо за ваш вопрос. Для получения подробной консультации по {product_name}, пожалуйста, свяжитесь с нами напрямую. Мы с радостью ответим на все ваши вопросы.',
                'is_system' => true,
                'keywords' => ['вопрос', 'как', 'почему', 'можно ли', 'когда', '?'],
            ],

            // Complaints
            [
                'name' => 'Ответ на жалобу',
                'category' => 'complaint',
                'template_text' => 'Здравствуйте! Примите наши извинения за возникшую ситуацию. Мы серьезно относимся к каждой жалобе. Пожалуйста, свяжитесь с нашей службой поддержки для оперативного решения вопроса.',
                'is_system' => true,
                'rating_min' => 1,
                'rating_max' => 2,
                'keywords' => ['жалоба', 'возмущен', 'неприемлемо', 'ужасно', 'кошмар'],
            ],

            // Generic fallback
            [
                'name' => 'Универсальный ответ',
                'category' => 'neutral',
                'template_text' => 'Здравствуйте, {customer_name}! Спасибо за ваш отзыв о {product_name}. Ваше мнение очень важно для нас. Если у вас есть какие-либо вопросы или предложения, пожалуйста, напишите нам.',
                'is_system' => true,
                'rating_min' => 1,
                'rating_max' => 5,
            ],
        ];

        foreach ($templates as $template) {
            // Check if template already exists
            $exists = ReviewTemplate::where('name', $template['name'])
                ->where('is_system', true)
                ->exists();

            if (! $exists) {
                ReviewTemplate::create($template);
            }
        }

        $this->command->info('Review templates seeded successfully!');
    }
}
