<?php

namespace App\Http\Livewire;

use App\Models\UzumShop;
use App\Models\OrderConfirmLog;
use App\Models\ReviewReplyLog;
use App\Jobs\AutoConfirmFbsOrders;
use App\Jobs\AutoReplyReviews;
use App\Services\UzumSellerApi;
use App\Services\UzumSellerAuth;
use Livewire\Component;
use Livewire\WithPagination;

class UzumAutomation extends Component
{
    use WithPagination;

    // ─── Настройки магазина ───
    public ?int $shopId = null;
    public bool $autoConfirmEnabled = false;
    public bool $autoReplyEnabled = false;
    public string $reviewTone = 'friendly';
    public string $apiToken = '';

    // ─── Seller Login ───
    public string $sellerEmail = '';
    public string $sellerPassword = '';
    public bool $showSellerLogin = false;
    public bool $sellerConnected = false;
    public string $sellerLoginError = '';

    // ─── UI state ───
    public string $activeTab = 'orders'; // orders | reviews | logs
    public bool $showTokenInput = false;

    // ─── Статистика ───
    public int $pendingOrdersCount = 0;
    public int $todayConfirmed = 0;
    public int $todayReplied = 0;

    public function mount(): void
    {
        $shop = $this->getCurrentShop();

        if ($shop) {
            $this->shopId = $shop->id;
            $this->autoConfirmEnabled = $shop->auto_confirm_enabled;
            $this->autoReplyEnabled = $shop->auto_reply_enabled;
            $this->reviewTone = $shop->review_tone ?? 'friendly';
            $this->sellerConnected = !empty($shop->session_token) || !empty($shop->seller_email);
            $this->loadStats();
        }
    }

    // ─── Seller Login ───

    public function connectSeller(): void
    {
        $this->sellerLoginError = '';

        if (empty($this->sellerEmail) || empty($this->sellerPassword)) {
            $this->sellerLoginError = 'Введи email и пароль';
            return;
        }

        $shop = $this->getCurrentShop();
        if (!$shop) {
            $this->sellerLoginError = 'Магазин не найден';
            return;
        }

        $auth = new UzumSellerAuth();
        $result = $auth->loginAndSave($shop, $this->sellerEmail, $this->sellerPassword);

        if ($result['success']) {
            // Сохраняем credentials для авто-ре-логина
            $shop->update([
                'seller_email'    => $this->sellerEmail,
                'seller_password' => $this->sellerPassword,
            ]);

            $this->sellerConnected = true;
            $this->showSellerLogin = false;
            $this->sellerEmail = '';
            $this->sellerPassword = '';

            $this->dispatch('notify', ['message' => 'Seller.uzum.uz подключён! Авто-ответ на отзывы готов к работе.']);
        } else {
            $this->sellerLoginError = $result['error'] ?? 'Ошибка авторизации';
        }
    }

    public function disconnectSeller(): void
    {
        $shop = $this->getCurrentShop();
        if (!$shop) return;

        $shop->update([
            'session_token'    => null,
            'refresh_token'    => null,
            'token_expires_at' => null,
            'seller_email'     => null,
            'seller_password'  => null,
            'auto_reply_enabled' => false,
        ]);

        $this->sellerConnected = false;
        $this->autoReplyEnabled = false;

        $this->dispatch('notify', ['message' => 'Seller.uzum.uz отключён']);
    }

    public function loadStats(): void
    {
        $shop = $this->getCurrentShop();
        if (!$shop) return;

        // Количество подтверждённых сегодня
        $this->todayConfirmed = OrderConfirmLog::where('status', 'confirmed')
            ->whereDate('confirmed_at', today())
            ->count();

        // Количество ответов сегодня
        $this->todayReplied = ReviewReplyLog::where('uzum_shop_id', $shop->uzum_shop_id)
            ->where('status', 'sent')
            ->whereDate('replied_at', today())
            ->count();

        // Новые заказы (через API)
        try {
            if ($shop->api_token) {
                $api = UzumSellerApi::forToken($shop->api_token);
                $result = $api->getFbsOrdersCount(
                    shopIds: [$shop->uzum_shop_id],
                    status: 'CREATED'
                );

                $this->pendingOrdersCount = $result['success']
                    ? ($result['data']['payload'] ?? 0)
                    : 0;
            }
        } catch (\Throwable) {
            $this->pendingOrdersCount = 0;
        }
    }

    // ─── Действия ───

    public function toggleAutoConfirm(): void
    {
        $shop = $this->getCurrentShop();
        if (!$shop) return;

        $this->autoConfirmEnabled = !$this->autoConfirmEnabled;
        $shop->update(['auto_confirm_enabled' => $this->autoConfirmEnabled]);

        $this->dispatch('notify', [
            'message' => $this->autoConfirmEnabled
                ? 'Авто-подтверждение включено'
                : 'Авто-подтверждение выключено',
        ]);
    }

    public function toggleAutoReply(): void
    {
        $shop = $this->getCurrentShop();
        if (!$shop) return;

        $this->autoReplyEnabled = !$this->autoReplyEnabled;
        $shop->update(['auto_reply_enabled' => $this->autoReplyEnabled]);

        $this->dispatch('notify', [
            'message' => $this->autoReplyEnabled
                ? 'Авто-ответ на отзывы включён'
                : 'Авто-ответ на отзывы выключён',
        ]);
    }

    public function updateTone(): void
    {
        $shop = $this->getCurrentShop();
        if (!$shop) return;

        $shop->update(['review_tone' => $this->reviewTone]);

        $this->dispatch('notify', ['message' => 'Тон ответов обновлён']);
    }

    public function saveToken(): void
    {
        $shop = $this->getCurrentShop();
        if (!$shop) return;

        $shop->update(['api_token' => $this->apiToken]);
        $this->showTokenInput = false;
        $this->apiToken = '';

        $this->dispatch('notify', ['message' => 'API токен сохранён']);
    }

    public function runConfirmNow(): void
    {
        AutoConfirmFbsOrders::dispatch();

        $this->dispatch('notify', [
            'message' => 'Авто-подтверждение запущено! Результат через минуту.',
        ]);
    }

    public function runReplyNow(): void
    {
        AutoReplyReviews::dispatch();

        $this->dispatch('notify', [
            'message' => 'Авто-ответ на отзывы запущен!',
        ]);
    }

    // ─── Helpers ───

    protected function getCurrentShop(): ?UzumShop
    {
        return UzumShop::where('user_id', auth()->id())->first();
    }

    public function getConfirmLogsProperty()
    {
        return OrderConfirmLog::latest()
            ->take(20)
            ->get();
    }

    public function getReplyLogsProperty()
    {
        $shop = $this->getCurrentShop();
        if (!$shop) return collect();

        return ReviewReplyLog::where('uzum_shop_id', $shop->uzum_shop_id)
            ->latest()
            ->take(20)
            ->get();
    }

    public function render()
    {
        return view('livewire.uzum-automation');
    }
}
