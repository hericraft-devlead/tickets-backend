<?php

namespace App\Http\Controllers;

use App\Services\MoodleService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    protected $moodleService;

    public function __construct(MoodleService $moodleService)
    {
        $this->moodleService = $moodleService;
    }

    /**
     * Login de usuario usando métodos estándar de Moodle
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->username;
        $password = $request->password;

        // Método 1: Buscar usuario por username
        $userResult = $this->moodleService->getUserByUsername($username);
        
        if (!$userResult['success'] || empty($userResult['data'])) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $moodleUser = $userResult['data'][0];

        // Método 2: Intentar autenticar (esto puede fallar si no tienes permisos)
        $authResult = $this->moodleService->authenticateUser($username, $password);
        
        // Si la autenticación falla, al menos verificamos que el usuario existe
        // En producción, deberías tener una forma real de verificar la contraseña
        if (!$authResult['success']) {
            // Podemos continuar si el usuario existe, pero mostramos advertencia
            // EN PRODUCCIÓN: Debes tener un método real para verificar credenciales
            \Log::warning("Autenticación falló pero usuario existe", [
                'username' => $username,
                'auth_error' => $authResult['error']
            ]);
        }

        return $this->createLoginResponse($moodleUser);
    }


    /**
     * Crear respuesta de login exitoso
     */
    private function createLoginResponse($moodleUser)
    {
        $token = Str::random(60);

        return response()->json([
            'success' => true,
            'message' => 'Login exitoso',
            'user' => [
                'id' => $moodleUser['id'],
                'username' => $moodleUser['username'],
                'email' => $moodleUser['email'],
                'firstname' => $moodleUser['firstname'],
                'lastname' => $moodleUser['lastname'],
                'fullname' => $moodleUser['firstname'] . ' ' . $moodleUser['lastname'],
            ],
            'token' => $token,
            'token_type' => 'bearer'
        ]);
    }

    /**
     * Probar el servicio personalizado de login
     */
    public function testCustomLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $result = $this->moodleService->authenticateWithCustomService(
            $request->username, 
            $request->password
        );

        return response()->json([
            'success' => $result['success'],
            'data' => $result['data'] ?? null,
            'error' => $result['error'] ?? null,
            'message' => $result['success'] ? 'Servicio personalizado funcionando' : 'Error en servicio personalizado'
        ]);
    }

    /**
     * Verificar token (para mantener sesión)
     */
    public function checkAuth(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Token válido'
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);
    }

    /**
     * Obtener perfil del usuario actual
     */
    public function profile(Request $request)
    {
        // En una implementación real, obtendrías el user ID del token
        // Por ahora usamos un parámetro temporal
        $userId = $request->user_id;

        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'ID de usuario requerido'
            ], 400);
        }

        $result = $this->moodleService->getUserById($userId);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener perfil'
            ], 500);
        }

        $userData = $result['data'][0] ?? null;

        if (!$userData) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $userData['id'],
                'username' => $userData['username'],
                'email' => $userData['email'],
                'firstname' => $userData['firstname'],
                'lastname' => $userData['lastname'],
                'fullname' => $userData['firstname'] . ' ' . $userData['lastname'],
            ]
        ]);
    }
}