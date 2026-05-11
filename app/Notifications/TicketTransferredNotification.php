<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketTransferredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Ticket $ticket,
        public User $transferredBy,
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

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🔄 Ticket Transferido a tu Departamento')
            ->greeting('Hola ' . $notifiable->name . '!')
            ->line('Un ticket ha sido transferido a tu departamento.')
            ->line('')
            ->line('📌 **Ticket:** #' . $this->ticket->id . ' - ' . $this->ticket->title)
            ->line('🏢 **Departamento Anterior:** ' . ($this->ticket->category->department->name ?? 'N/A'))
            ->line('👤 **Transferido por:** ' . $this->transferredBy->name)
            ->when($this->reason, function (MailMessage $mail) {
                return $mail->line('📝 **Motivo:** ' . $this->reason);
            })
            ->line('')
            ->line('**Detalles del Ticket:**')
            ->line('📂 Categoría: ' . $this->ticket->category->name)
            ->line('⚡ Prioridad: ' . $this->ticket->priority->name)
            ->line('📊 Estado: ' . $this->ticket->status->name)
            ->line('')
            ->line('Por favor, revisa el ticket lo antes posible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ticket_transferred',
            'ticket_id' => $this->ticket->id,
            'ticket_title' => $this->ticket->title,
            'transferred_by_id' => $this->transferredBy->id,
            'transferred_by_name' => $this->transferredBy->name,
            'reason' => $this->reason,
            'department_id' => $this->ticket->department_id,
            'message' => "Ticket #{$this->ticket->id} ha sido transferido a tu departamento por {$this->transferredBy->name}",
            'action_url' => $this->getTicketUrl(),
            'icon' => '🔄',
            'color' => 'warning'
        ];
    }

    /**
     * Get the ticket URL.
     */
    private function getTicketUrl(): string
    {
        return config('app.frontend_url') . '/admin/tickets/' . $this->ticket->id;
    }
}