<?php

namespace Tests\Feature\Api;

use App\Models\Ticket;
use App\Models\TicketResponse;
use App\Models\User;
use App\Notifications\TicketResponse as TicketResponseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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
        Notification::fake();

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

        Notification::assertSentTo(
            $user,
            TicketResponseNotification::class
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
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAsVerifiedApiUser($user)
            ->post('/api/v1/tickets', [
                'title' => 'With attachment',
                'message' => 'Ticket body text',
                'priority' => 'high',
                'attachment' => UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $ticket = Ticket::where('title', 'With attachment')->first();
        $this->assertNotNull($ticket->attachment);
        Storage::disk('local')->assertExists($ticket->attachment);
        $response->assertJsonPath(
            'data.attachment_url',
            route('api.v1.tickets.attachment', ['ticket' => $ticket->id])
        );
    }

    public function test_user_can_update_ticket_with_attachment(): void
    {
        Storage::fake('local');
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
                'attachment' => UploadedFile::fake()->image('shot.jpg'),
            ], ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertNotNull($ticket->fresh()->attachment);
        Storage::disk('local')->assertExists($ticket->fresh()->attachment);
    }

    public function test_owner_can_download_ticket_attachment(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $path = UploadedFile::fake()
            ->create('report.pdf', 100, 'application/pdf')
            ->store('attachments');

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'Download me',
            'message' => 'Body',
            'priority' => 'low',
            'attachment' => $path,
        ]);

        $this->actingAsVerifiedApiUser($user)
            ->get("/api/v1/tickets/{$ticket->id}/attachment")
            ->assertOk();
    }

    public function test_guest_cannot_download_ticket_attachment(): void
    {
        Storage::fake('local');
        $path = UploadedFile::fake()
            ->create('report.pdf', 100, 'application/pdf')
            ->store('attachments');

        $ticket = Ticket::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'Private file',
            'message' => 'Body',
            'priority' => 'low',
            'attachment' => $path,
        ]);

        $this->getJson("/api/v1/tickets/{$ticket->id}/attachment")
            ->assertUnauthorized();
    }

    public function test_other_user_cannot_download_ticket_attachment(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $path = UploadedFile::fake()
            ->create('report.pdf', 100, 'application/pdf')
            ->store('attachments');

        $ticket = Ticket::create([
            'user_id' => $owner->id,
            'title' => 'Private file',
            'message' => 'Body',
            'priority' => 'low',
            'attachment' => $path,
        ]);

        $this->actingAsVerifiedApiUser($other)
            ->getJson("/api/v1/tickets/{$ticket->id}/attachment")
            ->assertForbidden();
    }

    public function test_download_missing_ticket_attachment_returns_404(): void
    {
        $user = User::factory()->create();
        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'No file',
            'message' => 'Body',
            'priority' => 'low',
        ]);

        $this->actingAsVerifiedApiUser($user)
            ->getJson("/api/v1/tickets/{$ticket->id}/attachment")
            ->assertNotFound();
    }

    public function test_owner_can_download_response_attachment(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $path = UploadedFile::fake()
            ->create('reply.pdf', 100, 'application/pdf')
            ->store('attachments');

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'title' => 'With reply file',
            'message' => 'Body',
            'priority' => 'low',
        ]);

        $response = TicketResponse::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'message' => 'Reply with file',
            'attachment' => $path,
        ]);

        $this->actingAsVerifiedApiUser($user)
            ->get("/api/v1/tickets/{$ticket->id}/responses/{$response->id}/attachment")
            ->assertOk();
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
