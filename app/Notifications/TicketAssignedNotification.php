<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public ?string $notes = null 
    ) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('🎯 Ticket asignado a ti')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Se te ha asignado un nuevo ticket para atender.')
            ->line('')
            ->line('📌 **Título:** ' . $this->ticket->title)
            ->line('📝 **Descripción:** ' . $this->ticket->description)
            ->line('📂 **Categoría:** ' . $this->ticket->category->name)
            ->line('⚡ **Prioridad:** ' . $this->ticket->priority->name)
            ->line('📧 **Email de contacto:** ' . $this->ticket->contact_email);

        // Agregar notas si existen
        if ($this->notes) {
            $mail->line('')
                 ->line('📋 **Notas de asignación:** ' . $this->notes);
        }

        return $mail->line('')
            ->line('Por favor atiéndelo lo antes posible.');
    }
}