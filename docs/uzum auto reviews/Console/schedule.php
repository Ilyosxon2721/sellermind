<?php

use Illuminate\Support\Facades\Schedule;
use App\Jobs\AutoConfirmFbsOrders;
use App\Jobs\AutoReplyReviews;

/*
|--------------------------------------------------------------------------
| Scheduler: Uzum Auto-Processing
|--------------------------------------------------------------------------
|
| Добавь эти строки в routes/console.php (Laravel 11+)
| или в app/Console/Kernel.php → schedule() (Laravel 10)
|
*/

// Авто-подтверждение заказов — каждые 15 минут
Schedule::job(new AutoConfirmFbsOrders())
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('uzum:auto-confirm')
    ->when(fn () => config('uzum.auto_confirm.enabled', false));

// Авто-ответ на отзывы — каждые 30 минут
Schedule::job(new AutoReplyReviews())
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('uzum:auto-reply')
    ->when(fn () => config('uzum.auto_reply.enabled', false));
