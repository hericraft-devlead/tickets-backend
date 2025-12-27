<?php

namespace App\Http\Controllers;

use App\Services\MoodleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class MoodleController extends Controller
{
    protected $moodleService;

    public function __construct(MoodleService $moodleService)
    {
        $this->moodleService = $moodleService;
    }

    /**
     * Obtener información de un usuario
     */
    public function getUser($userId)
    {
        $result = $this->moodleService->getUserById($userId);

        if (!$result['success']) {
            return response()->json([
                'error' => $result['error']
            ], 500);
        }

        return response()->json($result['data']);
    }

    /**
     * Obtener cursos de un usuario
     */
    public function getUserCourses($userId)
    {
        $result = $this->moodleService->getUserCourses($userId);

        if (!$result['success']) {
            return response()->json([
                'error' => $result['error']
            ], 500);
        }

        return response()->json($result['data']);
    }

    /**
     * Obtener todos los cursos
     */
    public function getCourses()
    {
        $result = $this->moodleService->getCourses();

        if (!$result['success']) {
            return response()->json([
                'error' => $result['error']
            ], 500);
        }

        return response()->json($result['data']);
    }

    /**
     * Función genérica para llamar a cualquier función de Moodle
     */
    public function callFunction(Request $request)
    {
        $request->validate([
            'function' => 'required|string',
            'params' => 'sometimes|array'
        ]);

        $result = $this->moodleService->call(
            $request->function,
            $request->params ?? []
        );

        if (!$result['success']) {
            return response()->json([
                'error' => $result['error']
            ], 500);
        }

        return response()->json($result['data']);
    }

    /**
     * Obtener usuario por username
     */
    public function getUserByUsername($username)
    {
        try {
            \Log::info("Buscando usuario: " . $username);
            
            $result = $this->moodleService->getUserByUsername($username);

            \Log::info("Resultado:", ['success' => $result['success'], 'data_count' => count($result['data'] ?? [])]);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'username' => $username
                ], 500);
            }

            if (empty($result['data'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado',
                    'username' => $username
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data']
            ]);

        } catch (\Exception $e) {
            \Log::error("Error en getUserByUsername: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUserInfoData($userId)
    {
        try {
            $data = DB::connection('moodle')
                ->table('j5r5_user_info_data')
                ->select('id', 'userid', 'fieldid', 'data', 'dataformat')
                ->where('userid', $userId)
                ->get();

            return response()->json([
                'success' => true,
                'userid' => $userId,
                'count' => $data->count(),
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al consultar mdl_user_info_data: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Error al consultar datos de Moodle',
            ], 500);
        }
    }
}