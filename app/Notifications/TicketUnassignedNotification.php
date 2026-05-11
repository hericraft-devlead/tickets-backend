<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketUnassignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Ticket $ticket,
        public ?string $reason = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🔓 Ticket Desasignado')
            ->greeting('Hola ' . $notifiable->name . '!')
            ->line('Has sido desasignado de un ticket.')
            ->line('')
            ->line('📌 **Ticket:** #' . $this->ticket->id . ' - ' . $this->ticket->title)
            ->when($this->reason, function (MailMessage $mail) {
                return $mail->line('📝 **Motivo:** ' . $this->reason);
            })
            ->line('')
            ->line('**Detalles del Ticket:**')
            ->line('📂 Categoría: ' . $this->ticket->category->name)
            ->line('⚡ Prioridad: ' . $this->ticket->priority->name)
            ->line('📊 Estado: ' . $this->ticket->status->name)
            ->line('')
            ->line('El ticket ha sido reasignado a otro miembro del equipo.');
    }

    /**
     * Get the array representation for database storage.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ticket_unassigned',
            'ticket_id' => $this->ticket->id,
            'ticket_title' => $this->ticket->title,
            'reason' => $this->reason,
            'department_id' => $this->ticket->department_id,
            'message' => "Has sido desasignado del ticket #{$this->ticket->id}",
            'action_url' => $this->getDashboardUrl(),
            'icon' => '🔓',
            'color' => 'info'
        ];
    }

    /**
     * Get the dashboard URL.
     */
    private function getDashboardUrl(): string
    {
        return config('app.frontend_url') . '/admin/dashboard';
    }
}