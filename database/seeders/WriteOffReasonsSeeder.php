<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Warehouse\WriteOffReason;
use Illuminate\Database\Seeder;

class WriteOffReasonsSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $this->seedReasonsForCompany($company->id);
        }
    }

    public function seedReasonsForCompany(int $companyId): void
    {
        $defaultReasons = WriteOffReason::getDefaultReasons();

        foreach ($defaultReasons as $reason) {
            WriteOffReason::firstOrCreate(
                [
                    'company_id' => $companyId,
                    'code' => $reason['code'],
                ],
                array_merge($reason, ['company_id' => $companyId])
            );
        }
    }
}
