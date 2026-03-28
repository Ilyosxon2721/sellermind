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

    private const VALID_SOURCES = ['all', 'wb', 'ozon', 'uzum', 'ym', 'manual', 'offline'];

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
     * Получить валидированный источник из запроса
     */
    private function getSource(Request $request): string
    {
        $source = $request->input('source', 'all');

        return in_array($source, self::VALID_SOURCES) ? $source : 'all';
    }

    /**
     * ABC-анализ товаров
     */
    public function abcAnalysis(Request $request): JsonResponse
    {
        $this->configureCurrencyService();
        $companyId = $this->getCompanyId();
        $period = $request->input('period', '30days');
        $source = $this->getSource($request);

        $cacheKey = "business_abc_{$companyId}_{$period}_{$source}";
        $data = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($companyId, $period, $source) {
            return $this->service->getAbcAnalysis($companyId, $period, $source);
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
        $source = $this->getSource($request);

        $cacheKey = "business_abcxyz_{$companyId}_{$period}_{$source}";
        $data = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($companyId, $period, $source) {
            return $this->service->getAbcxyzAnalysis($companyId, $period, $source);
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
     * Рейтинг товаров по продажам
     */
    public function salesRanking(Request $request): JsonResponse
    {
        $this->configureCurrencyService();
        $companyId = $this->getCompanyId();
        $period = $request->input('period', '30days');
        $source = $this->getSource($request);

        $cacheKey = "business_sales_ranking_{$companyId}_{$period}_{$source}";
        $data = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($companyId, $period, $source) {
            return $this->service->getProductSalesRanking($companyId, $period, $source);
        });

        return response()->json($data);
    }

    /**
     * Рейтинг товаров по маржинальности
     */
    public function marginRanking(Request $request): JsonResponse
    {
        $this->configureCurrencyService();
        $companyId = $this->getCompanyId();
        $period = $request->input('period', '30days');
        $source = $this->getSource($request);

        $cacheKey = "business_margin_ranking_{$companyId}_{$period}_{$source}";
        $data = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($companyId, $period, $source) {
            return $this->service->getProductMarginRanking($companyId, $period, $source);
        });

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
