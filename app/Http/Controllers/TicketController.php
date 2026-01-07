<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Category;
use App\Models\TicketStatus;
use App\Models\User;
use App\Http\Requests\TicketRequest;
use App\Http\Requests\TicketUpdateRequest; 
use App\Notifications\NewTicketNotification;
use App\Notifications\TicketAssignedNotification;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    // Método existente para obtener todos los tickets
    public function getTickets()
    {
        return response()->json(
            Ticket::with(['category','priority','status','tags','moodleUser','assignedUser'])
                ->latest()
                ->paginate(10),
            Response::HTTP_OK
        );
    }

    // Método para obtener un ticket específico
    public function getTicket($id)
    {
        $ticket = Ticket::with([
            'category',
            'priority', 
            'status', 
            'tags',
            'moodleUser',
            'assignedUser:id,name,email' 
        ])->findOrFail($id);

        return response()->json($ticket, Response::HTTP_OK);
    }

    public function createTicket(TicketRequest $request)
    {
        $category = Category::findOrFail($request->category_id);
        $status = TicketStatus::findOrFail(1); 

        $ticket = Ticket::create([
            'title' => $request->title,
            'description' => $request->description,

            'category_id' => $request->category_id,
            'priority_id' => $request->priority_id,
            'department_id' => $category->department_id,

            'status_id' => $status->id,

            'moodle_user_id' => $request->moodle_user_id,

            'contact_name' => $request->contact_name,
            'contact_email' => $request->contact_email,
        ]);

        $users = User::where('department_id', $ticket->department_id)->get();
        Notification::send($users, new NewTicketNotification($ticket));

        return response()->json([
            'message' => 'Ticket creado correctamente',
            'ticket' => $ticket->load(['category', 'priority', 'status']),
        ], Response::HTTP_CREATED);
    }


    public function updateTicket(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = $request->user();
        
        if (!$user->isAdmin() && $ticket->assigned_user_id !== $user->id) {
            return response()->json([
                'message' => 'No tienes permiso para actualizar este ticket'
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'priority_id' => 'sometimes|exists:priorities,id',
            'assigned_user_id' => 'nullable|exists:users,id',
            'status_id' => 'sometimes|exists:ticket_statuses,id',
            'contact_name' => 'sometimes|string|max:255',
            'contact_email' => 'sometimes|email',
            'closed_at' => 'nullable|date',
        ]);
        

        $oldAssignedUserId = $ticket->assigned_user_id;

        $ticket->update($validated);
        
        if ($request->has('assigned_user_id') && 
            $request->assigned_user_id && 
            $oldAssignedUserId != $request->assigned_user_id) {
            
            $assignedUser = User::find($request->assigned_user_id);
            if ($assignedUser) {
                $assignedUser->notify(new TicketAssignedNotification($ticket));
            }
        }
        

        if ($request->has('status_id') && $request->status_id == 4) { 
            $ticket->update(['closed_at' => now()]);
        }
        
        return response()->json([
            'message' => 'Ticket actualizado correctamente',
            'ticket' => $ticket->load(['category', 'priority', 'status', 'assignedUser']),
        ], Response::HTTP_OK);
    }

    public function deleteTicket(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Solo administradores pueden eliminar tickets'
            ], Response::HTTP_FORBIDDEN);
        }
        
        $ticket->delete();
        
        return response()->json([
            'message' => 'Ticket eliminado correctamente'
        ], Response::HTTP_OK);
    }


    public function getTicketsByMoodleUser($moodleUserId)
    {
        $tickets = Ticket::with(['category','priority','status','tags','assignedUser'])
            ->where('moodle_user_id', $moodleUserId)
            ->latest()
            ->paginate(10);

        return response()->json($tickets, Response::HTTP_OK);
    }

    public function getTicketsAssignedToLocalUser($userId)
    {
        $tickets = Ticket::with(['category','priority','status','tags','moodleUser'])
            ->where('assigned_user_id', $userId)
            ->latest()
            ->paginate(10);

        return response()->json($tickets, Response::HTTP_OK);
    }

    public function getUnassignedTickets()
    {
        $tickets = Ticket::with(['category','priority','status','tags','moodleUser'])
            ->whereNull('assigned_user_id')
            ->latest()
            ->paginate(10);

        return response()->json($tickets, Response::HTTP_OK);
    }
}