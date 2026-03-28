<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Company;
use App\Services\BusinessAnalyticsService;
use App\Services\CurrencyConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BusinessAnalyticsController extends Controller
{
    use HasCompanyScope;

    public function __construct(
        protected BusinessAnalyticsService $service,
        protected CurrencyConversionService $currencyService,
    ) {}

    /**
     * Настроить сервис конвертации валют для текущей компании
     */
    private function configureCurrencyService(): void
    {
        $companyId = $this->getCompanyId();

        if ($companyId) {
            $company = Company::find($companyId);
            if ($company) {
                $this->currencyService->forCompany($company);
            }
        }
    }

    /**
     * ABC-анализ товаров
     */
    public function abcAnalysis(Request $request): JsonResponse
    {
        $this->configureCurrencyService();
        $companyId = $this->getCompanyId();
        $period = $request->input('period', '30days');

        $cacheKey = "business_abc_{$companyId}_{$period}";
        $data = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($companyId, $period) {
            return $this->service->getAbcAnalysis($companyId, $period);
        });

        return response()->json($data);
    }

    /**
     * ABCXYZ-анализ клиентов
     */
    public function abcxyzAnalysis(Request $request): JsonResponse
    {
        $this->configureCurrencyService();
        $companyId = $this->getCompanyId();
        $period = $request->input('period', '90days');

        $cacheKey = "business_abcxyz_{$companyId}_{$period}";
        $data = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($companyId, $period) {
            return $this->service->getAbcxyzAnalysis($companyId, $period);
        });

        return response()->json($data);
    }

    /**
     * Получить SWOT-анализ
     */
    public function swotAnalysis(): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $data = $this->service->getSwotAnalysis($companyId);

        return response()->json($data);
    }

    /**
     * Сохранить SWOT-анализ
     */
    public function saveSwotAnalysis(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $validated = $request->validate([
            'strengths' => 'array',
            'strengths.*' => 'string|max:500',
            'weaknesses' => 'array',
            'weaknesses.*' => 'string|max:500',
            'opportunities' => 'array',
            'opportunities.*' => 'string|max:500',
            'threats' => 'array',
            'threats.*' => 'string|max:500',
        ]);

        $data = $this->service->saveSwotAnalysis($companyId, $validated);

        return response()->json($data);
    }
}
