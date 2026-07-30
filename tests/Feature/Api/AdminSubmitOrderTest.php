<?php

namespace Tests\Feature\Api;

use App\Models\SubmitOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubmitOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_submitted_orders(): void
    {
        SubmitOrder::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'phone' => '09121111111',
            'subject' => 'Custom order',
            'message' => 'Need a model',
        ]);

        $this->actingAsAdminApiUser()
            ->getJson('/api/v1/admin/orders')
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Customer');
    }

    public function test_admin_can_view_submitted_order(): void
    {
        $order = SubmitOrder::create([
            'name' => 'Customer',
            'email' => 'customer@example.com',
            'phone' => '09121111111',
            'subject' => 'Custom order',
            'message' => 'Need a model',
        ]);

        $this->actingAsAdminApiUser()
            ->getJson("/api/v1/admin/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.message', 'Need a model');
    }
}
