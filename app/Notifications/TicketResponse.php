<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketResponse extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private Ticket $ticket
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'پاسخ جدید',
            'icon' => 'fas fa-envelope',
            'time' => now()->diffForHumans(),
            'sender' => 'پشتیبانی',
            'message' => 'پاسخ جدیدی برای تیکت شما ثبت شد.',
            'url' => rtrim((string) config('app.frontend_url'), '/').'/tickets/'.$this->ticket->getKey(),
        ];
    }
}
