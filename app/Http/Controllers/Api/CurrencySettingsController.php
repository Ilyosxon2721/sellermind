<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CurrencyConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencySettingsController extends Controller
{
    protected CurrencyConversionService $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Get currency settings for company
     */
    public function index(Request $request): JsonResponse
    {
        $company = $request->user()->currentCompany;

        if (!$company) {
            return response()->json(['message' => 'Компания не выбрана'], 400);
        }

        $service = $this->currencyService->forCompany($company);

        return response()->json([
            'display_currency' => $service->getDisplayCurrency(),
            'display_currency_symbol' => $service->getCurrencySymbol(),
            'exchange_rates' => $service->getCompanyRates(),
            'current_rub_uzs_rate' => $service->getRate('RUB', 'UZS'),
            'available_currencies' => [
                ['code' => 'UZS', 'name' => 'Узбекский сум', 'symbol' => 'сўм'],
                ['code' => 'RUB', 'name' => 'Российский рубль', 'symbol' => '₽'],
                ['code' => 'KZT', 'name' => 'Казахстанский тенге', 'symbol' => '₸'],
                ['code' => 'USD', 'name' => 'Доллар США', 'symbol' => '$'],
            ],
        ]);
    }

    /**
     * Update display currency
     */
    public function updateDisplayCurrency(Request $request): JsonResponse
    {
        $request->validate([
            'currency' => 'required|string|in:UZS,RUB,KZT,USD,EUR',
        ]);

        $company = $request->user()->currentCompany;

        if (!$company) {
            return response()->json(['message' => 'Компания не выбрана'], 400);
        }

        $service = $this->currencyService->forCompany($company);
        $service->setDisplayCurrency($request->currency);

        return response()->json([
            'message' => 'Валюта отображения обновлена',
            'display_currency' => $service->getDisplayCurrency(),
            'display_currency_symbol' => $service->getCurrencySymbol(),
        ]);
    }

    /**
     * Update exchange rate
     */
    public function updateRate(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
            'rate' => 'required|numeric|min:0.0001',
        ]);

        $company = $request->user()->currentCompany;

        if (!$company) {
            return response()->json(['message' => 'Компания не выбрана'], 400);
        }

        $service = $this->currencyService->forCompany($company);
        $service->setCompanyRate($request->from, $request->to, $request->rate);

        return response()->json([
            'message' => 'Курс валюты обновлён',
            'rate' => [
                'from' => $request->from,
                'to' => $request->to,
                'value' => $request->rate,
            ],
        ]);
    }

    /**
     * Convert amount to display currency
     */
    public function convert(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric',
            'from' => 'nullable|string|size:3',
        ]);

        $company = $request->user()->currentCompany;

        if (!$company) {
            return response()->json(['message' => 'Компания не выбрана'], 400);
        }

        $service = $this->currencyService->forCompany($company);
        $from = $request->get('from', 'RUB');
        $to = $service->getDisplayCurrency();

        $converted = $service->convert($request->amount, $from, $to);

        return response()->json([
            'original' => [
                'amount' => $request->amount,
                'currency' => $from,
            ],
            'converted' => [
                'amount' => $converted,
                'currency' => $to,
                'formatted' => $service->format($converted),
            ],
            'rate' => $service->getRate($from, $to),
        ]);
    }
}
