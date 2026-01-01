<?php

namespace Database\Seeders;

use App\Models\Warehouse\Unit;
use App\Models\Warehouse\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseCoreSeeder extends Seeder
{
    public function run(): void
    {
        // Seed default unit
        Unit::firstOrCreate(['code' => 'pcs'], ['name' => 'Шт']);

        // Seed default channels if table exists
        if (DB::getSchemaBuilder()->hasTable('channels')) {
            $channels = [
                ['code' => 'UZUM', 'name' => 'Uzum'],
                ['code' => 'WB', 'name' => 'Wildberries'],
                ['code' => 'OZON', 'name' => 'Ozon'],
                ['code' => 'YM', 'name' => 'Yandex Market'],
            ];
            foreach ($channels as $channel) {
                $payload = ['name' => $channel['name']];
                if (DB::getSchemaBuilder()->hasColumn('channels', 'company_id')) {
                    $payload['company_id'] = DB::table('companies')->value('id');
                }
                DB::table('channels')->updateOrInsert(
                    ['code' => $channel['code']],
                    $payload
                );
            }
        }

        // Seed default warehouse for first company if exists
        if (DB::getSchemaBuilder()->hasTable('companies')) {
            $companyId = DB::table('companies')->value('id');
            if ($companyId) {
                Warehouse::firstOrCreate(
                    ['company_id' => $companyId, 'name' => 'Main'],
                    ['is_default' => true]
                );
            }
        }
    }
}
