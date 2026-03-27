<?php

declare(strict_types=1);

namespace Database\Factories\Finance;

use App\Models\Company;
use App\Models\Finance\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика для модели Employee
 *
 * @extends Factory<Employee>
 */
final class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'middle_name' => fake()->optional()->firstName(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'position' => fake()->jobTitle(),
            'department' => fake()->randomElement(['Продажи', 'Маркетинг', 'Логистика']),
            'hire_date' => fake()->dateTimeBetween('-3 years', '-1 month'),
            'salary_type' => Employee::SALARY_TYPE_FIXED,
            'base_salary' => fake()->numberBetween(300000, 2000000),
            'currency_code' => 'UZS',
            'is_active' => true,
        ];
    }

    /**
     * Уволенный сотрудник
     */
    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => [
            'termination_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'is_active' => false,
        ]);
    }
}
