<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Store\Store;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Управление доменами магазина — поддомен и кастомный домен
 */
final class StoreDomainController extends Controller
{
    use ApiResponder, HasCompanyScope;

    /**
     * Получить информацию о домене магазина
     */
    public function show(int $storeId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $baseDomain = config('app.store_base_domain', 'sellermind.uz');

        return $this->successResponse([
            'subdomain' => $store->slug . '.' . $baseDomain,
            'subdomain_url' => 'https://' . $store->slug . '.' . $baseDomain,
            'path_url' => config('app.url') . '/store/' . $store->slug,
            'custom_domain' => $store->custom_domain,
            'domain_verified' => $store->domain_verified,
            'ssl_enabled' => $store->ssl_enabled,
            'setup_instructions' => $store->custom_domain ? $this->getDnsInstructions($store) : null,
        ]);
    }

    /**
     * Привязать кастомный домен
     */
    public function update(int $storeId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $data = $request->validate([
            'custom_domain' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/'],
        ]);

        $domain = $data['custom_domain'] ? mb_strtolower(trim($data['custom_domain'])) : null;

        // Проверяем что домен не занят другим магазином
        if ($domain) {
            $exists = Store::where('custom_domain', $domain)
                ->where('id', '!=', $store->id)
                ->exists();

            if ($exists) {
                return $this->errorResponse(
                    'Этот домен уже привязан к другому магазину',
                    'domain_taken',
                    'custom_domain',
                    422
                );
            }
        }

        $store->update([
            'custom_domain' => $domain,
            'domain_verified' => false,
            'ssl_enabled' => false,
        ]);

        $baseDomain = config('app.store_base_domain', 'sellermind.uz');

        return $this->successResponse([
            'custom_domain' => $store->custom_domain,
            'domain_verified' => false,
            'subdomain' => $store->slug . '.' . $baseDomain,
            'setup_instructions' => $domain ? $this->getDnsInstructions($store) : null,
            'message' => $domain
                ? 'Домен сохранён. Настройте DNS-записи и запустите верификацию.'
                : 'Кастомный домен отвязан.',
        ]);
    }

    /**
     * Проверить DNS-записи кастомного домена
     */
    public function verify(int $storeId): JsonResponse
    {
        $store = $this->findStore($storeId);

        if (! $store->custom_domain) {
            return $this->errorResponse('Кастомный домен не указан', 'no_domain', status: 422);
        }

        $domain = $store->custom_domain;
        $serverIp = config('app.server_ip', gethostbyname(parse_url(config('app.url'), PHP_URL_HOST) ?: 'sellermind.uz'));
        $baseDomain = config('app.store_base_domain', 'sellermind.uz');

        // Проверяем A-запись или CNAME
        $verified = false;
        $checks = [];

        // Проверка A-записи
        $aRecords = @dns_get_record($domain, DNS_A);
        if ($aRecords) {
            foreach ($aRecords as $record) {
                if ($record['ip'] === $serverIp) {
                    $verified = true;
                    $checks[] = ['type' => 'A', 'value' => $record['ip'], 'status' => 'ok'];
                } else {
                    $checks[] = ['type' => 'A', 'value' => $record['ip'], 'status' => 'wrong', 'expected' => $serverIp];
                }
            }
        }

        // Проверка CNAME
        if (! $verified) {
            $cnameRecords = @dns_get_record($domain, DNS_CNAME);
            if ($cnameRecords) {
                foreach ($cnameRecords as $record) {
                    $target = rtrim($record['target'], '.');
                    if ($target === $baseDomain || str_ends_with($target, '.' . $baseDomain)) {
                        $verified = true;
                        $checks[] = ['type' => 'CNAME', 'value' => $target, 'status' => 'ok'];
                    } else {
                        $checks[] = ['type' => 'CNAME', 'value' => $target, 'status' => 'wrong', 'expected' => $baseDomain];
                    }
                }
            }
        }

        if (empty($checks)) {
            $checks[] = ['type' => 'DNS', 'status' => 'not_found', 'message' => 'DNS-записи не найдены'];
        }

        // Обновляем статус верификации
        if ($verified && ! $store->domain_verified) {
            $store->update([
                'domain_verified' => true,
                'ssl_enabled' => true,
            ]);
        }

        return $this->successResponse([
            'domain' => $domain,
            'verified' => $verified,
            'checks' => $checks,
            'message' => $verified
                ? 'Домен верифицирован! Магазин доступен по адресу https://' . $domain
                : 'DNS-записи не настроены. Добавьте A или CNAME запись.',
        ]);
    }

    /**
     * Инструкции по настройке DNS
     */
    private function getDnsInstructions(Store $store): array
    {
        $serverIp = config('app.server_ip', gethostbyname(parse_url(config('app.url'), PHP_URL_HOST) ?: 'sellermind.uz'));
        $baseDomain = config('app.store_base_domain', 'sellermind.uz');

        return [
            'option_a' => [
                'title' => 'A-запись (рекомендуется)',
                'type' => 'A',
                'name' => $store->custom_domain,
                'value' => $serverIp,
                'ttl' => '3600',
            ],
            'option_b' => [
                'title' => 'CNAME-запись',
                'type' => 'CNAME',
                'name' => $store->custom_domain,
                'value' => $baseDomain,
                'ttl' => '3600',
            ],
            'note' => 'Настройте одну из записей в DNS-панели вашего домена. Изменения могут занять до 24 часов.',
        ];
    }

    private function findStore(int $storeId): Store
    {
        return Store::where('company_id', $this->getCompanyId())->findOrFail($storeId);
    }
}
