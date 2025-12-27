<?php

namespace App\Http\Controllers;

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
    public function getUsers()
    {
        $users = User::with('department:id,name')->get();

        return response()->json($users, Response::HTTP_OK);
    }

    /**
     * Obtener usuario por ID (ADMIN)
     */
    public function getUsersById($id)
    {
        $user = User::with('department:id,name')
            ->select('id', 'name', 'email', 'type', 'department_id')
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
}
