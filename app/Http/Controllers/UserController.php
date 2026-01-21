<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Login LOCAL
     */
    public function login(LoginRequest $request)
    {
        $request->validated();

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = User::with('department')->where('email', $request->email)->first();

        $token = $user->createToken('local-token')->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión exitoso',
            'token'   => $token,
            'user'    => $user,
            'status'  => Response::HTTP_OK,
        ]);
    }

    /**
     * Logout
     */
    public function logout()
    {
        $user = Auth::user();

        if ($user) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Sesión cerrada exitosamente',
        ], Response::HTTP_OK);
    }

    /**
     * Listar usuarios (ADMIN)
     */
    public function getUsers(Request $request)
    {
        $query = User::with('department');
        
        // Búsqueda por nombre o email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Filtrar por departamento
        if ($request->has('department_id') && $request->department_id) {
            $query->where('department_id', $request->department_id);
        }
        
        // Paginación
        $perPage = $request->get('limit', 10);
        $users = $query->paginate($perPage);
        
        return response()->json([
            'data' => $users->items(),
            'total' => $users->total(),
            'current_page' => $users->currentPage(),
            'per_page' => $users->perPage(),
            'last_page' => $users->lastPage()
        ]);
    }

    /**
     * Obtener usuario por ID (ADMIN)
     */
    public function getUsersById($id)
    {
        $user = User::with('department:id,name')
            ->select('id', 'name', 'email', 'role', 'department_id')
            ->where('id', $id)
            ->firstOrFail();

        return response()->json($user, Response::HTTP_OK);
    }

    /**
     * Crear usuario (ADMIN)
     */
    public function createUser(UserRequest $request)
    {
        $data = $request->validated();

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return response(null, Response::HTTP_CREATED);
    }

    /**
     * Actualizar usuario (ADMIN)
     */
    public function updateUser(UserRequest $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response(null, Response::HTTP_NOT_FOUND);
        }

        $data = $request->validated();

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Eliminar usuario (ADMIN)
     */
    public function deleteUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response(null, Response::HTTP_NOT_FOUND);
        }

        $user->delete();

        return response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Perfil del usuario autenticado
     */
    public function getProfile()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user->load('department:id,name');

        return response()->json($user, Response::HTTP_OK);
    }

    /**
     * Actualizar perfil propio
     */
    public function updateProfile(UserRequest $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->validated();

        $allowed = ['name', 'email', 'password', 'department_id'];
        $data = array_intersect_key($data, array_flip($allowed));

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'user'    => $user->load('department:id,name')
        ], Response::HTTP_OK);
    }

    /**
     * Obtener usuarios por departamento
     */
    public function getUsersByDepartment($departmentId)
    {
        try {
            $users = User::where('department_id', $departmentId)
                ->select('id', 'name', 'email', 'role', 'department_id', 'created_at')
                ->orderBy('name')
                ->get()
                ->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'role_name' => $user->getRoleName(),
                        'department_id' => $user->department_id,
                        'is_admin' => $user->isAdmin(),
                        'is_department_head' => $user->isDepartmentHead(),
                        'is_support_agent' => $user->isSupportAgent(),
                        'created_at' => $user->created_at->format('Y-m-d H:i:s')
                    ];
                });
                
            return response()->json([
                'success' => true,
                'users' => $users,
                'count' => $users->count()
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            \Log::error('Error obteniendo usuarios por departamento:', [
                'department_id' => $departmentId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios del departamento'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
