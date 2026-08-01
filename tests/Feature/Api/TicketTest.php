<?php

namespace Tests\Feature\Api;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_list_own_tickets(): void
    {
        $user = User::factory()->create();
        Ticket::create([
            'user_id' => $user->id,
            'title' => 'Help needed',
            'message' => 'Please assist',
            'priority' => 'medium',
        ]);

        $this->actingAsVerifiedApiUser($user)
            ->getJson('/api/v1/tickets')
            ->assertOk()
            ->assertJsonPath('data.data.0.title', 'Help needed');
    }

    public function test_verified_user_can_create_ticket(): void
    {
        $user = User::factory()->create();

        $this->actingAsVerifiedApiUser($user)
            ->postJson('/api/v1/tickets', [
                'title' => 'New ticket',
                'message' => 'Ticket body text',
                'priority' => 'high',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'پیام شما با موفقیت ارسال شد.');

        $this->assertDatabaseHas('tickets', [
            'user_id' => $user->id,
            'title' => 'New ticket',
        ]);
    }

    public function test_user_can_view_own_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'View me',
            'message' => 'Details',
            'priority' => 'low',
        ]);

        $this->actingAsVerifiedApiUser($user)
            ->getJson("/api/v1/tickets/{$ticket->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'View me');
    }

    public function test_user_can_update_own_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Old title',
            'message' => 'Old message',
            'priority' => 'low',
        ]);

        $this->actingAsVerifiedApiUser($user)
            ->putJson("/api/v1/tickets/{$ticket->id}", [
                'title' => 'New title',
                'message' => 'New message',
                'priority' => 'medium',
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'New title')
            ->assertJsonPath('message', 'تیکت شما با موفقیت بروزرسانی شد.');
    }

    public function test_user_can_respond_to_ticket(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $user = User::factory()->create();
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Respond',
            'message' => 'Body',
            'priority' => 'low',
        ]);

        $this->actingAsVerifiedApiUser($user)
            ->postJson("/api/v1/tickets/{$ticket->id}/responses", [
                'message' => 'Thanks for the update',
            ])
            ->assertOk();

        $this->assertDatabaseHas('ticket_responses', [
            'ticket_id' => $ticket->id,
            'message' => 'Thanks for the update',
        ]);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $user,
            \App\Notifications\TicketResponse::class
        );
    }

    public function test_admin_can_list_all_tickets(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        Ticket::create([
            'user_id' => $user->id,
            'title' => 'User ticket',
            'message' => 'Body',
            'priority' => 'low',
        ]);

        $this->actingAsVerifiedApiUser($admin)
            ->getJson('/api/v1/tickets')
            ->assertOk()
            ->assertJsonPath('data.data.0.title', 'User ticket');
    }

    public function test_user_can_create_ticket_with_attachment(): void
    {
        $user = User::factory()->create();

        $this->actingAsVerifiedApiUser($user)
            ->post('/api/v1/tickets', [
                'title' => 'With attachment',
                'message' => 'Ticket body text',
                'priority' => 'high',
                'attachment' => \Illuminate\Http\UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $ticket = Ticket::where('title', 'With attachment')->first();
        $this->assertNotNull($ticket->attachment);
    }

    public function test_user_can_update_ticket_with_attachment(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Old title',
            'message' => 'Old message',
            'priority' => 'low',
        ]);

        $this->actingAsVerifiedApiUser($user)
            ->post("/api/v1/tickets/{$ticket->id}", [
                '_method' => 'PUT',
                'title' => 'New title',
                'message' => 'New message',
                'priority' => 'medium',
                'attachment' => \Illuminate\Http\UploadedFile::fake()->image('shot.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertNotNull($ticket->fresh()->attachment);
    }

    public function test_admin_can_delete_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = Ticket::create([
            'user_id' => $admin->id,
            'title' => 'Delete me',
            'message' => 'Body',
            'priority' => 'low',
        ]);

        $this->actingAsVerifiedApiUser($admin)
            ->deleteJson("/api/v1/tickets/{$ticket->id}")
            ->assertOk();

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_regular_user_cannot_delete_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Keep me',
            'message' => 'Body',
            'priority' => 'low',
        ]);

        $this->actingAsVerifiedApiUser($user)
            ->deleteJson("/api/v1/tickets/{$ticket->id}")
            ->assertForbidden();
    }
}
