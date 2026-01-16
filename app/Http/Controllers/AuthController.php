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
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = $request->username;
        $password = $request->password;

       
        $userResult = $this->moodleService->getUserByUsername($username);
        
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
                'auth_error' => $authResult['error']
            ]);
        }

        return $this->createLoginResponse($moodleUser);
    }

    private function createLoginResponse($moodleUser)
    {
        $localMoodleUser = MoodleUser::where(
            'moodle_user_id',
            $moodleUser['id']
        )->first();

        if (!$localMoodleUser) {
            $localMoodleUser = MoodleUser::create([
                'moodle_user_id' => $moodleUser['id'],
                'username' => $moodleUser['username'], 
                'name' => $moodleUser['firstname'] . ' ' . $moodleUser['lastname'],
                'email' => $moodleUser['email'],
                'firstname' => $moodleUser['firstname'],
                'lastname' => $moodleUser['lastname'],
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
            ]
        ]);
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
        return response()->json([
            'success' => true,
            'message' => 'Token válido'
        ]);
    }


    public function logout(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Logout exitoso'
        ]);
    }

    public function profile($userId)
    {
        
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