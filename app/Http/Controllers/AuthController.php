<?php

namespace App\Http\Controllers;

use App\Services\MoodleService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\MoodleUser;

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
        \Log::info('Intento de login', ['username' => $request->username]);
        
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->username;
        $password = $request->password;

        $userResult = $this->moodleService->getUserByUsername($username);
        
        \Log::info('Resultado de getUserByUsername', [
            'success' => $userResult['success'],
            'has_data' => !empty($userResult['data']),
            'data_count' => isset($userResult['data']) ? count($userResult['data']) : 0
        ]);
        
        if (!$userResult['success'] || empty($userResult['data'])) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $moodleUser = $userResult['data'][0];
        
        $authResult = $this->moodleService->authenticateUser($username, $password);
        
        if (!$authResult['success']) {
            \Log::warning("Autenticación falló pero usuario existe", [
                'username' => $username,
                'auth_error' => $authResult['error'] ?? 'Error desconocido'
            ]);
        }

        return $this->createLoginResponse($moodleUser);
    }

    private function createLoginResponse($moodleUser)
    {
        try {
            $localMoodleUser = MoodleUser::where('moodle_user_id', $moodleUser['id'])->first();

            if (!$localMoodleUser) {
                $localMoodleUser = MoodleUser::create([
                    'moodle_user_id' => $moodleUser['id'],
                    'username' => $moodleUser['username'], 
                    'name' => ($moodleUser['firstname'] ?? '') . ' ' . ($moodleUser['lastname'] ?? ''),
                    'email' => $moodleUser['email'] ?? null,
                    'firstname' => $moodleUser['firstname'] ?? null,
                    'lastname' => $moodleUser['lastname'] ?? null,
                ]);
            } else {
                $localMoodleUser->update([
                    'username' => $moodleUser['username'] ?? $localMoodleUser->username,
                    'name' => ($moodleUser['firstname'] ?? '') . ' ' . ($moodleUser['lastname'] ?? ''),
                    'email' => $moodleUser['email'] ?? $localMoodleUser->email,
                    'firstname' => $moodleUser['firstname'] ?? $localMoodleUser->firstname,
                    'lastname' => $moodleUser['lastname'] ?? $localMoodleUser->lastname,
                ]);
            }

            $localMoodleUser->tokens()->delete();
            
            $token = $localMoodleUser->createToken(
                'moodle-token-' . $localMoodleUser->moodle_user_id, 
                ['moodle:access'],
                now()->addDays(7)
            )->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login exitoso',
                'token' => $token, 
                'token_type' => 'bearer',
                'expires_in' => 7 * 24 * 60 * 60,
                'moodle_user' => [
                    'id' => $localMoodleUser->moodle_user_id, 
                    'moodle_user_id' => $localMoodleUser->moodle_user_id, 
                    'name' => $localMoodleUser->name,
                    'email' => $localMoodleUser->email,
                    'username' => $localMoodleUser->username,
                    'firstname' => $localMoodleUser->firstname,
                    'lastname' => $localMoodleUser->lastname,
                ],
                'user' => [
                    'id' => $localMoodleUser->moodle_user_id, 
                    'moodle_user_id' => $localMoodleUser->moodle_user_id, 
                    'name' => $localMoodleUser->name,
                    'email' => $localMoodleUser->email,
                    'username' => $localMoodleUser->username,
                    'firstname' => $localMoodleUser->firstname,
                    'lastname' => $localMoodleUser->lastname,
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error en createLoginResponse', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el login: ' . $e->getMessage()
            ], 500);
        }
    }

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

    public function checkAuth(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado'
            ], 401);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Token válido',
            'user' => [
                'id' => $user->moodle_user_id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
            ]
        ]);
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if ($user) {
                $user->currentAccessToken()->delete();
                \Log::info('Logout exitoso', ['user_id' => $user->id]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Logout exitoso'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en logout', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cerrar sesión'
            ], 500);
        }
    }

    public function profile(Request $request, $userId = null)
    {
        $userId = $userId ?? $request->user()?->moodle_user_id;
        
        if (!$userId) {
            return response()->json([
                'success' => false,
                'message' => 'ID de usuario requerido'
            ], 400);
        }

        \Log::info('Obteniendo perfil de usuario', ['user_id' => $userId]);

        $result = $this->moodleService->getUserById($userId);

        if (!$result['success']) {
            \Log::error('Error al obtener perfil de Moodle', [
                'user_id' => $userId,
                'error' => $result['error'] ?? 'Error desconocido'
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener perfil'
            ], 500);
        }

        $userData = $result['data'][0] ?? null;

        if (!$userData) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado en Moodle'
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
                'fullname' => ($userData['firstname'] ?? '') . ' ' . ($userData['lastname'] ?? ''),
            ]
        ]);
    }
}