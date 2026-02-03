<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты безопасности SalesManagementController
 * Проверка защиты от IDOR атак
 */
class SalesManagementSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $userCompanyA;

    protected User $userCompanyB;

    protected Company $companyA;

    protected Company $companyB;

    protected Sale $saleCompanyA;

    protected Sale $saleCompanyB;

    protected function setUp(): void
    {
        parent::setUp();

        // Создаём две компании
        $this->companyA = Company::factory()->create(['name' => 'Company A']);
        $this->companyB = Company::factory()->create(['name' => 'Company B']);

        // Создаём пользователей для каждой компании
        $this->userCompanyA = User::factory()->create([
            'company_id' => $this->companyA->id,
            'email' => 'user.a@company-a.com',
        ]);

        $this->userCompanyB = User::factory()->create([
            'company_id' => $this->companyB->id,
            'email' => 'user.b@company-b.com',
        ]);

        // Создаём продажи для каждой компании
        $this->saleCompanyA = Sale::factory()->create([
            'company_id' => $this->companyA->id,
            'status' => 'draft',
            'created_by' => $this->userCompanyA->id,
        ]);

        $this->saleCompanyB = Sale::factory()->create([
            'company_id' => $this->companyB->id,
            'status' => 'draft',
            'created_by' => $this->userCompanyB->id,
        ]);
    }

    /**
     * Тест: пользователь не может просмотреть продажу другой компании
     */
    public function test_user_cannot_view_sale_from_another_company(): void
    {
        $this->actingAs($this->userCompanyA);

        $response = $this->getJson("/api/sales-management/{$this->saleCompanyB->id}");

        $response->assertStatus(404);
    }

    /**
     * Тест: пользователь может просмотреть продажу своей компании
     */
    public function test_user_can_view_sale_from_own_company(): void
    {
        $this->actingAs($this->userCompanyA);

        $response = $this->getJson("/api/sales-management/{$this->saleCompanyA->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $this->saleCompanyA->id);
    }

    /**
     * Тест: пользователь не может обновить продажу другой компании
     */
    public function test_user_cannot_update_sale_from_another_company(): void
    {
        $this->actingAs($this->userCompanyA);

        $response = $this->putJson("/api/sales-management/{$this->saleCompanyB->id}", [
            'notes' => 'Trying to update another company sale',
        ]);

        $response->assertStatus(404);

        // Проверяем что продажа не изменилась
        $this->saleCompanyB->refresh();
        $this->assertNotEquals('Trying to update another company sale', $this->saleCompanyB->notes);
    }

    /**
     * Тест: пользователь может обновить продажу своей компании
     */
    public function test_user_can_update_sale_from_own_company(): void
    {
        $this->actingAs($this->userCompanyA);

        $response = $this->putJson("/api/sales-management/{$this->saleCompanyA->id}", [
            'notes' => 'Updated notes',
        ]);

        $response->assertStatus(200);

        $this->saleCompanyA->refresh();
        $this->assertEquals('Updated notes', $this->saleCompanyA->notes);
    }

    /**
     * Тест: пользователь не может удалить продажу другой компании
     */
    public function test_user_cannot_delete_sale_from_another_company(): void
    {
        $this->actingAs($this->userCompanyA);

        $response = $this->deleteJson("/api/sales-management/{$this->saleCompanyB->id}");

        $response->assertStatus(404);

        // Проверяем что продажа всё ещё существует
        $this->assertDatabaseHas('sales', [
            'id' => $this->saleCompanyB->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Тест: пользователь может удалить продажу своей компании
     */
    public function test_user_can_delete_sale_from_own_company(): void
    {
        $this->actingAs($this->userCompanyA);

        $response = $this->deleteJson("/api/sales-management/{$this->saleCompanyA->id}");

        $response->assertStatus(200);

        // Проверяем что продажа soft deleted
        $this->assertSoftDeleted('sales', [
            'id' => $this->saleCompanyA->id,
        ]);
    }

    /**
     * Тест: пользователь не может подтвердить продажу другой компании
     */
    public function test_user_cannot_confirm_sale_from_another_company(): void
    {
        $this->actingAs($this->userCompanyA);

        $response = $this->postJson("/api/sales-management/{$this->saleCompanyB->id}/confirm");

        $response->assertStatus(404);

        // Проверяем что продажа не подтверждена
        $this->saleCompanyB->refresh();
        $this->assertEquals('draft', $this->saleCompanyB->status);
    }

    /**
     * Тест: пользователь не может завершить продажу другой компании
     */
    public function test_user_cannot_complete_sale_from_another_company(): void
    {
        // Сначала подтверждаем продажу компании B
        $this->saleCompanyB->update(['status' => 'confirmed']);

        $this->actingAs($this->userCompanyA);

        $response = $this->postJson("/api/sales-management/{$this->saleCompanyB->id}/complete");

        $response->assertStatus(404);
    }

    /**
     * Тест: пользователь не может отменить продажу другой компании
     */
    public function test_user_cannot_cancel_sale_from_another_company(): void
    {
        $this->actingAs($this->userCompanyA);

        $response = $this->postJson("/api/sales-management/{$this->saleCompanyB->id}/cancel");

        $response->assertStatus(404);

        // Проверяем что продажа не отменена
        $this->saleCompanyB->refresh();
        $this->assertNotEquals('cancelled', $this->saleCompanyB->status);
    }

    /**
     * Тест: пользователь не может отгрузить товары из продажи другой компании
     */
    public function test_user_cannot_ship_items_from_sale_of_another_company(): void
    {
        $this->actingAs($this->userCompanyA);

        $response = $this->postJson("/api/sales-management/{$this->saleCompanyB->id}/ship");

        $response->assertStatus(404);
    }

    /**
     * Тест: пользователь не может получить резервы продажи другой компании
     */
    public function test_user_cannot_get_reservations_from_sale_of_another_company(): void
    {
        $this->actingAs($this->userCompanyA);

        $response = $this->getJson("/api/sales-management/{$this->saleCompanyB->id}/reservations");

        $response->assertStatus(404);
    }

    /**
     * Тест: пользователь не может добавить позицию в продажу другой компании
     */
    public function test_user_cannot_add_item_to_sale_of_another_company(): void
    {
        $this->actingAs($this->userCompanyA);

        $response = $this->postJson("/api/sales-management/{$this->saleCompanyB->id}/items", [
            'product_name' => 'Test Product',
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        $response->assertStatus(404);
    }

    /**
     * Тест: пользователь может добавить позицию в продажу своей компании
     */
    public function test_user_can_add_item_to_sale_of_own_company(): void
    {
        $this->actingAs($this->userCompanyA);

        $response = $this->postJson("/api/sales-management/{$this->saleCompanyA->id}/items", [
            'product_name' => 'Test Product',
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.product_name', 'Test Product');
    }

    /**
     * Тест: список продаж показывает только продажи своей компании
     */
    public function test_sales_list_shows_only_own_company_sales(): void
    {
        $this->actingAs($this->userCompanyA);

        $response = $this->getJson('/api/sales-management');

        $response->assertStatus(200);

        $salesIds = collect($response->json('data'))->pluck('id')->all();

        // Проверяем что в списке есть продажа компании A
        $this->assertContains($this->saleCompanyA->id, $salesIds);

        // Проверяем что в списке нет продажи компании B
        $this->assertNotContains($this->saleCompanyB->id, $salesIds);
    }
}
