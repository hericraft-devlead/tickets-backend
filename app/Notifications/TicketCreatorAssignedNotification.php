<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketCreatorAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public User $assignedUser,
        public ?string $notes = null
    ) {
        // Asegurar que las relaciones estén cargadas
        if (!$ticket->relationLoaded('category')) {
            $ticket->load('category');
        }
        if (!$ticket->relationLoaded('priority')) {
            $ticket->load('priority');
        }
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('✅ Tu ticket ha sido asignado a un especialista')
            ->greeting('Hola ' . $this->ticket->contact_name)
            ->line('Tu ticket ha sido asignado a un especialista para su atención.')
            ->line('')
            ->line('📌 **Título:** ' . $this->ticket->title)
            ->line('📝 **Descripción:** ' . $this->ticket->description)
            ->line('📂 **Categoría:** ' . $this->ticket->category->name)
            ->line('⚡ **Prioridad:** ' . $this->ticket->priority->name)
            ->line('')
            ->line('👤 **Especialista asignado:**')
            ->line('   • **Nombre:** ' . $this->assignedUser->name)
            ->line('   • **Email:** ' . $this->assignedUser->email);

        // Agregar notas si existen
        if ($this->notes) {
            $mail->line('')
                 ->line('📋 **Notas del especialista:** ' . $this->notes);
        }

        $frontendUrl = config('app.frontend_url', url('/'));

        return $mail->line('')
            ->line('El especialista se pondrá en contacto contigo pronto.')
            ->line('')
            ->line('¡Gracias por tu paciencia!');
    }

}