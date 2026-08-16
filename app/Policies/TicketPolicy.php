<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TicketPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        return $ticket->user->is($user) || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return Auth::check();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        return $ticket->user->is($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether user can respond to the ticket.
     */
    public function respond(User $user, Ticket $ticket): bool
    {
        return $ticket->user->is($user) || $user->hasRole('admin');
    }
}
