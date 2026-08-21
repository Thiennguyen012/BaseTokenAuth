<?php

namespace Tests\Feature;

use App\Models\Categories\Category;
use App\Models\CustomerContacts\CustomerContact;
use App\Models\Users\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerContactApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_contact_form_without_authentication(): void
    {
        $category = Category::query()->create(['category_name' => 'Ống nhựa']);

        $this->postJson('/api/customer-contacts', [
            'full_name' => 'Nguyễn Văn A',
            'phone' => '0901 234 567',
            'email' => 'customer@example.com',
            'category_id' => $category->id,
            'consultation_content' => 'Tôi cần tư vấn sản phẩm.',
        ])->assertCreated()
            ->assertJsonPath('message', 'Đã tiếp nhận yêu cầu tư vấn.')
            ->assertJsonPath('data.category.id', $category->id);

        $this->assertDatabaseHas('customer_contacts', [
            'phone' => '0901 234 567',
            'category_id' => $category->id,
        ]);
    }

    public function test_public_contact_form_is_validated(): void
    {
        $this->postJson('/api/customer-contacts', [
            'full_name' => '',
            'phone' => 'invalid-phone',
            'email' => 'invalid-email',
            'consultation_content' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['full_name', 'phone', 'email', 'consultation_content']);
    }

    public function test_admin_customer_contact_crud_requires_authentication(): void
    {
        $this->getJson('/admin/api/customer-contacts')->assertUnauthorized();
        $this->postJson('/admin/api/customer-contacts', [])->assertUnauthorized();
    }

    public function test_admin_can_manage_customer_contacts(): void
    {
        Sanctum::actingAs(User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-contact@example.com',
            'password' => 'password',
        ]));
        $contact = CustomerContact::query()->create([
            'full_name' => 'Khách hàng',
            'phone' => '0901234567',
            'consultation_content' => 'Nội dung ban đầu',
        ]);

        $this->getJson('/admin/api/customer-contacts?search=0901234567')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $contact->id);

        $this->getJson("/admin/api/customer-contacts/{$contact->id}")
            ->assertOk()
            ->assertJsonPath('data.full_name', 'Khách hàng');

        $this->patchJson("/admin/api/customer-contacts/{$contact->id}", [
            'consultation_content' => 'Nội dung đã cập nhật',
        ])->assertOk()
            ->assertJsonPath('data.consultation_content', 'Nội dung đã cập nhật');

        $this->deleteJson("/admin/api/customer-contacts/{$contact->id}")
            ->assertOk();

        $this->assertDatabaseMissing('customer_contacts', ['id' => $contact->id]);
    }
}
