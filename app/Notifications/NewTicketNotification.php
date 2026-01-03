<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewTicketNotification extends Notification
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('📩 Nuevo ticket recibido')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Se ha creado un nuevo ticket en tu departamento.')
            ->line('📌 Título: ' . $this->ticket->title)
            ->line('📂 Categoría: ' . $this->ticket->category->name)
            ->line('⚡ Prioridad: ' . $this->ticket->priority->name)
            ->action(
                'Ver ticket',
                config('app.frontend_url') . '/tickets/' . $this->ticket->id
            )
            ->line('Por favor atiéndelo lo antes posible.');
    }
}
