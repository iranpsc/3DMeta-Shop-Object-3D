<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketResponse as TicketResponseNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;

class TicketService
{
    public function paginateForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return Ticket::query()
            ->when(
                ! $user->hasRole('admin'),
                fn ($query) => $query->where('user_id', $user->id)
            )
            ->with('user:id,name,email')
            ->latest()
            ->paginate($perPage);
    }

    /**
     * @param  array{title: string, message: string, priority: string}  $data
     */
    public function store(User $user, array $data, ?UploadedFile $attachment = null): Ticket
    {
        Gate::authorize('create', Ticket::class);

        return Ticket::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'message' => $data['message'],
            'priority' => $data['priority'],
            'attachment' => $attachment?->store('attachments'),
        ]);
    }

    public function show(User $user, Ticket $ticket): Ticket
    {
        Gate::authorize('view', $ticket);

        return $ticket->load(['responses.user:id,name,avatar', 'user:id,name,avatar,email']);
    }

    /**
     * @param  array{title: string, message: string, priority: string}  $data
     */
    public function update(User $user, Ticket $ticket, array $data, ?UploadedFile $attachment = null): Ticket
    {
        Gate::authorize('update', $ticket);

        $ticket->update([
            'title' => $data['title'],
            'message' => $data['message'],
            'priority' => $data['priority'],
        ]);

        if ($attachment) {
            $ticket->update([
                'attachment' => $attachment->store('attachments'),
            ]);
        }

        return $ticket->fresh(['user:id,name,email']);
    }

    public function delete(User $user, Ticket $ticket): void
    {
        Gate::authorize('delete', $ticket);

        $ticket->delete();
    }

    public function respond(User $user, Ticket $ticket, string $message, ?UploadedFile $attachment = null): Ticket
    {
        Gate::authorize('respond', $ticket);

        $ticket->responses()->create([
            'message' => $message,
            'attachment' => $attachment?->store('attachments'),
            'user_id' => $user->id,
        ]);

        $ticket->update(['response_status' => 'replied']);
        $ticket->user->notify(new TicketResponseNotification($ticket));

        return $ticket->fresh(['responses.user:id,name,avatar', 'user:id,name,avatar,email']);
    }
}
