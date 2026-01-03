<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Category;
use App\Models\TicketStatus;
use App\Models\User;
use App\Http\Requests\TicketRequest;
use App\Notifications\NewTicketNotification;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Notification;

class TicketController extends Controller
{
    public function getTickets()
    {
        return response()->json(
            Ticket::with(['category','priority','status','tags'])
                ->latest()
                ->paginate(10),
            Response::HTTP_OK
        );
    }

    public function createTicket(TicketRequest $request)
    {
        $category = Category::findOrFail($request->category_id);
        $status = TicketStatus::where('is_default', true)->firstOrFail();

        $ticket = Ticket::create([
            ...$request->validated(),
            'department_id' => $category->department_id,
            'ticket_status_id' => $status->id,
            'user_id' => auth()->id(), 
        ]);

        $users = User::where('department_id', $ticket->department_id)->get();

        Notification::send($users, new NewTicketNotification($ticket));

        if ($request->filled('tags')) {
            $ticket->tags()->sync($request->tags);
        }

        return response()->json([
            'message' => 'Ticket creado correctamente',
            'ticket' => $ticket->load(['tags','category','priority','status'])
        ], Response::HTTP_CREATED);
    }
}
