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
use App\Notifications\TicketTransferredNotification;
use App\Notifications\TicketUnassignedNotification;
use App\Notifications\TicketCreatorAssignedNotification; // Nueva notificación
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function getTickets()
    {
        return response()->json(
            Ticket::with([
                'category',
                'priority',
                'status',
                'tags',
                'moodleUser',
                'assignedUser',
                'department'
            ])
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
            'assignedUser:id,name,email',
            'department' 
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

        if ($request->has('tag_ids') && is_array($request->tag_ids)) {
            $ticket->tags()->attach($request->tag_ids);
        }

        $users = User::where('department_id', $ticket->department_id)->get();
        Notification::send($users, new NewTicketNotification($ticket));

        return response()->json([
            'message' => 'Ticket creado correctamente',
            'ticket' => $ticket->load(['category', 'priority', 'status', 'tags']),
        ], Response::HTTP_CREATED);
    }


    public function updateTicket(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = $request->user();
        
        if (!$user->isAdmin() && !$user->isDepartmentHead() && $ticket->assigned_user_id !== $user->id) {
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
                // Notificar al usuario asignado
                $assignedUser->notify(new TicketAssignedNotification($ticket));
                
                // Notificar al creador del ticket (nuevo)
                $this->notifyTicketCreator($ticket, $assignedUser);
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
        $user = $request->user();
        
        if (!$user->isAdmin()) {
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
        $tickets = Ticket::with(['category','priority','status','tags','moodleUser','department'])
            ->where('assigned_user_id', $userId)
            ->latest()
            ->paginate(10);

        return response()->json($tickets, Response::HTTP_OK);
    }

    public function getUnassignedTickets()
    {
        $tickets = Ticket::with(['category','priority','status','tags','moodleUser','department'])
            ->whereNull('assigned_user_id')
            ->latest()
            ->paginate(10);

        return response()->json($tickets, Response::HTTP_OK);
    }


    public function getTicketsByDepartment(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }
        
        if (!$user->department_id) {
            return response()->json([
                'message' => 'El usuario no pertenece a ningún departamento'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $tickets = Ticket::with(['category', 'priority', 'status', 'tags', 'moodleUser', 'assignedUser', 'department'])
            ->where('department_id', $user->department_id)
            ->latest()
            ->paginate(10);
        
        return response()->json($tickets, Response::HTTP_OK);
    }

    public function getUnassignedTicketsByDepartment(Request $request)
    {
        $user = $request->user();
        
        if (!$user->department_id) {
            return response()->json([
                'message' => 'El usuario no pertenece a ningún departamento'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        $tickets = Ticket::with(['category', 'priority', 'status', 'tags', 'moodleUser'])
            ->where('department_id', $user->department_id)
            ->whereNull('assigned_user_id')
            ->latest()
            ->paginate(10);
        
        return response()->json($tickets, Response::HTTP_OK);
    }

    // Método para obtener tickets asignados a usuarios del mismo departamento
    public function getTicketsAssignedToDepartmentUsers(Request $request)
    {
        $user = $request->user();
        
        if (!$user->department_id) {
            return response()->json([
                'message' => 'El usuario no pertenece a ningún departamento'
            ], Response::HTTP_BAD_REQUEST);
        }
        
        // Obtener IDs de usuarios del mismo departamento
        $departmentUserIds = User::where('department_id', $user->department_id)
            ->pluck('id')
            ->toArray();
        
        $tickets = Ticket::with(['category', 'priority', 'status', 'tags', 'moodleUser', 'assignedUser'])
            ->where('department_id', $user->department_id)
            ->whereIn('assigned_user_id', $departmentUserIds)
            ->latest()
            ->paginate(10);
        
        return response()->json($tickets, Response::HTTP_OK);
    }


    /**
     * Asignar ticket a usuario del mismo departamento
     * Permisos: Super Admin, Jefe de Departamento
     */
    public function assignTicket(Request $request, $id)
    {
        try {
            $ticket = Ticket::findOrFail($id);
            $user = $request->user();
            
            // Validar que el usuario tenga permisos
            $this->validateAssignmentPermissions($user, $ticket);
            
            // Validar datos
            $validated = $request->validate([
                'assigned_user_id' => 'required|exists:users,id',
                'notes' => 'nullable|string|max:500'
            ]);
            
            $assignedUser = User::findOrFail($validated['assigned_user_id']);
            
            // Si es jefe de departamento, validar que el usuario sea de su departamento
            if ($user->isDepartmentHead()) {
                if ($assignedUser->department_id !== $user->department_id) {
                    return response()->json([
                        'message' => 'Solo puedes asignar tickets a usuarios de tu departamento'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            
            $oldAssignedUserId = $ticket->assigned_user_id;
            
            $ticket->update([
                'assigned_user_id' => $validated['assigned_user_id'],
                'status_id' => 2, 
            ]);
            
            // Notificar al usuario asignado
            if ($oldAssignedUserId != $validated['assigned_user_id']) {
                $assignedUser->notify(new TicketAssignedNotification($ticket, $validated['notes'] ?? null));
                
                // Notificar al creador del ticket (nuevo)
                $this->notifyTicketCreator($ticket, $assignedUser, $validated['notes'] ?? null);
                
                // Notificar al usuario anterior
                if ($oldAssignedUserId) {
                    $previousUser = User::find($oldAssignedUserId);
                    if ($previousUser) {
                        $previousUser->notify(new TicketUnassignedNotification($ticket));
                    }
                }
            }
            
            $this->logAssignment($ticket, $user, $assignedUser, $validated['notes'] ?? null);
            
            return response()->json([
                'message' => 'Ticket asignado correctamente',
                'ticket' => $ticket->load(['category', 'priority', 'status', 'assignedUser']),
                'assigned_to' => $assignedUser->name
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            \Log::error('Error asignando ticket:', [
                'ticket_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Error al asignar ticket',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Transferir ticket a otro departamento
     */
    public function transferTicket(Request $request, $id)
    {
        try {
            $ticket = Ticket::findOrFail($id);
            $user = $request->user();
            
            if (!$user->isAdmin() && !$user->isDepartmentHead()) { 
                return response()->json([
                    'message' => 'Solo el Super Administrador o Jefe de Departamento pueden transferir tickets'
                ], Response::HTTP_FORBIDDEN);
            }
            
            // Validar datos
            $validated = $request->validate([
                'department_id' => 'required|exists:departments,id',
                'reason' => 'nullable|string|max:500',
                'notify_users' => 'boolean|nullable' 
            ]);
            
            $oldDepartmentId = $ticket->department_id;
            
            // Actualizar ticket
            $ticket->update([
                'department_id' => $validated['department_id'],
                'assigned_user_id' => null, // Quitar asignación al cambiar de departamento
                'status_id' => 1 // Volver a "Abierto"
            ]);
            
            // Notificar a los usuarios del nuevo departamento si se solicita
            if ($request->get('notify_users', true)) {
                $newDepartmentUsers = User::where('department_id', $validated['department_id'])->get();
                Notification::send($newDepartmentUsers, new TicketTransferredNotification($ticket, $user, $validated['reason'] ?? null));
            }
            
            // Registrar la transferencia
            $this->logTransfer($ticket, $user, $oldDepartmentId, $validated['department_id'], $validated['reason'] ?? null);
            
            return response()->json([
                'message' => 'Ticket transferido correctamente',
                'ticket' => $ticket->load(['category', 'priority', 'status', 'department']),
                'old_department_id' => $oldDepartmentId,
                'new_department_id' => $validated['department_id']
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            \Log::error('Error transfiriendo ticket:', [
                'ticket_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Error al transferir ticket',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reasignar ticket (cambiar de usuario dentro del mismo departamento)
     * Permisos: Super Admin, Jefe de Departamento
     */
    public function reassignTicket(Request $request, $id)
    {
        try {
            $ticket = Ticket::findOrFail($id);
            $user = $request->user();
            
            if (!$ticket->assigned_user_id) {
                return response()->json([
                    'message' => 'El ticket no está asignado a ningún usuario'
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($user->isDepartmentHead() && $ticket->department_id !== $user->department_id) {
                return response()->json([
                    'message' => 'Solo puedes transferir tickets de tu propio departamento'
                ], Response::HTTP_FORBIDDEN);
            }
            
            $this->validateAssignmentPermissions($user, $ticket);
            
            $validated = $request->validate([
                'assigned_user_id' => 'required|exists:users,id',
                'reason' => 'nullable|string|max:500'
            ]);
            
            $newUser = User::findOrFail($validated['assigned_user_id']);
            
            // Si es jefe de departamento, validar que el nuevo usuario sea de su departamento
            if ($user->isDepartmentHead()) {
                if ($newUser->department_id !== $user->department_id) {
                    return response()->json([
                        'message' => 'Solo puedes reasignar a usuarios de tu departamento'
                    ], Response::HTTP_FORBIDDEN);
                }
            }
            
            $oldUserId = $ticket->assigned_user_id;
            
            $ticket->update([
                'assigned_user_id' => $validated['assigned_user_id'],
                'status_id' => 2 // Mantener en progreso
            ]);
            
            // Notificar al nuevo usuario
            $newUser->notify(new TicketAssignedNotification(
                $ticket, 
                'Ticket reasignado: ' . ($validated['reason'] ?? 'Sin motivo especificado')
            ));
            
            // Notificar al creador del ticket sobre la reasignación (nuevo)
            $this->notifyTicketCreator($ticket, $newUser, 
                'El ticket ha sido reasignado: ' . ($validated['reason'] ?? 'Sin motivo especificado')
            );
            
            // Notificar al usuario anterior
            $oldUser = User::find($oldUserId);
            if ($oldUser) {
                $oldUser->notify(new TicketUnassignedNotification(
                    $ticket, 
                    $validated['reason'] ?? null
                ));
            }
            
            $this->logReassignment($ticket, $user, $oldUser, $newUser, $validated['reason'] ?? null);
            
            return response()->json([
                'message' => 'Ticket reasignado correctamente',
                'ticket' => $ticket->load(['category', 'priority', 'status', 'assignedUser']),
                'old_user' => $oldUser ? $oldUser->name : null,
                'new_user' => $newUser->name
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            \Log::error('Error reasignando ticket:', [
                'ticket_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'message' => 'Error al reasignar ticket',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Método auxiliar para notificar al creador del ticket
     * (Nuevo método agregado)
     */
    private function notifyTicketCreator(Ticket $ticket, User $assignedUser, ?string $notes = null)
    {
        try {
            // Cargar relaciones necesarias si no están cargadas
            if (!$ticket->relationLoaded('category')) {
                $ticket->load('category');
            }
            if (!$ticket->relationLoaded('priority')) {
                $ticket->load('priority');
            }
            
            // Verificar que el ticket tenga email de contacto
            if (empty($ticket->contact_email)) {
                \Log::warning('Ticket sin email de contacto:', [
                    'ticket_id' => $ticket->id,
                    'contact_name' => $ticket->contact_name
                ]);
                return;
            }
            
            // Usar Notification facade para enviar al email del creador
            Notification::route('mail', [
                $ticket->contact_email => $ticket->contact_name
            ])->notify(new TicketCreatorAssignedNotification(
                $ticket, 
                $assignedUser, 
                $notes
            ));
            
            \Log::info('Notificación enviada al creador del ticket:', [
                'ticket_id' => $ticket->id,
                'to' => $ticket->contact_email,
                'assigned_to' => $assignedUser->name
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error notificando al creador del ticket:', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Método auxiliar para validar permisos de asignación
     */
    private function validateAssignmentPermissions($user, $ticket)
    {
        // Super Admin puede hacer cualquier cosa
        if ($user->isAdmin()) {
            return true;
        }
        
        // Jefe de Departamento solo puede asignar tickets de su departamento
        if ($user->isDepartmentHead()) {
            if ($ticket->department_id !== $user->department_id) {
                throw new \Exception('No tienes permisos para asignar tickets de otros departamentos');
            }
            return true;
        }
        
        // Agentes de soporte no pueden asignar tickets
        throw new \Exception('No tienes permisos para asignar tickets. Solo administradores y jefes de departamento pueden asignar tickets.');
    }


    private function logAssignment($ticket, $assignedBy, $assignedTo, $notes = null)
    {
        \Log::info('Ticket asignado', [
            'ticket_id' => $ticket->id,
            'assigned_by' => $assignedBy->id,
            'assigned_to' => $assignedTo->id,
            'department_id' => $ticket->department_id,
            'notes' => $notes
        ]);
        
    }

    private function logTransfer($ticket, $transferredBy, $fromDepartmentId, $toDepartmentId, $reason = null)
    {
        \Log::info('Ticket transferido', [
            'ticket_id' => $ticket->id,
            'transferred_by' => $transferredBy->id,
            'from_department' => $fromDepartmentId,
            'to_department' => $toDepartmentId,
            'reason' => $reason
        ]);
    }

    private function logReassignment($ticket, $reassignedBy, $fromUser, $toUser, $reason = null)
    {
        \Log::info('Ticket reasignado', [
            'ticket_id' => $ticket->id,
            'reassigned_by' => $reassignedBy->id,
            'from_user' => $fromUser ? $fromUser->id : null,
            'to_user' => $toUser->id,
            'reason' => $reason
        ]);
    }
}