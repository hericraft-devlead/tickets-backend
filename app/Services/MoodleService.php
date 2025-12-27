<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MoodleService
{
    protected $baseUrl;
    protected $token;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config("moodle.api_url");
        $this->token = config("moodle.token");
        $this->timeout = config("moodle.timeout");
    }

    public function call($function, $params = [])
    {
        try {
            $url = $this->baseUrl . "webservice/rest/server.php";
            
            $queryParams = array_merge([
                "wstoken" => $this->token,
                "wsfunction" => $function,
                "moodlewsrestformat" => "json"
            ], $params);

            $response = Http::timeout($this->timeout)
                ->get($url, $queryParams);

            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data["exception"])) {
                    Log::error("Moodle API Error", [
                        "function" => $function,
                        "error" => $data
                    ]);
                    return [
                        "success" => false,
                        "error" => $data["message"] ?? "Error desconocido"
                    ];
                }

                return [
                    "success" => true,
                    "data" => $data
                ];
            }

            Log::error("Moodle API HTTP Error", [
                "function" => $function,
                "status" => $response->status(),
                "response" => $response->body()
            ]);

            return [
                "success" => false,
                "error" => "Error HTTP: " . $response->status()
            ];

        } catch (\Exception $e) {
            Log::error("Moodle API Exception", [
                "function" => $function,
                "exception" => $e->getMessage()
            ]);

            return [
                "success" => false,
                "error" => $e->getMessage()
            ];
        }
    }

    public function getUserById($userId)
    {
        return $this->call("core_user_get_users_by_field", [
            "field" => "id",
            "values[0]" => $userId
        ]);
    }

    public function getUserCourses($userId)
    {
        return $this->call("core_enrol_get_users_courses", [
            "userid" => $userId
        ]);
    }

    public function getCourses()
    {
        return $this->call("core_course_get_courses");
    }

    public function getCourseContents($courseId)
    {
        return $this->call("core_course_get_contents", [
            "courseid" => $courseId
        ]);
    }

    /**
     * Login usando el servicio personalizado AppServiceLogin
     */
    public function customLogin($username, $password)
    {
        return $this->call('AppServiceLogin', [
            'username' => $username,
            'password' => $password,
        ]);
    }

    /**
     * Autenticar usuario usando el servicio personalizado
     */
    public function authenticateWithCustomService($username, $password)
    {
        $result = $this->customLogin($username, $password);
        
        if ($result['success']) {
            // El servicio personalizado podría retornar diferentes estructuras
            // Ajusta según lo que retorne tu servicio específico
            return $result;
        }
        
        return $result;
    }

    /**
     * Obtener usuario por credenciales usando el servicio personalizado
     */
    public function getUserByCredentialsCustom($username, $password)
    {
        // Usar el servicio personalizado para login
        $loginResult = $this->authenticateWithCustomService($username, $password);
        
        if (!$loginResult['success']) {
            return $loginResult;
        }

        // Si el login fue exitoso, obtener información completa del usuario
        return $this->call('core_user_get_users_by_field', [
            'field' => 'username',
            'values[0]' => $username
        ]);
    }

    /**
     * Obtener usuario por credenciales (método estándar)
     */
    public function getUserByCredentials($username, $password)
    {
        // Primero intentamos autenticar con método estándar
        $authResult = $this->authenticateUser($username, $password);
        
        if (!$authResult['success']) {
            return $authResult;
        }

        // Si la autenticación fue exitosa, buscamos el usuario
        return $this->call('core_user_get_users_by_field', [
            'field' => 'username',
            'values[0]' => $username
        ]);
    }

    /**
     * Autenticar usuario en Moodle (método estándar)
     */
    public function authenticateUser($username, $password)
    {
        return $this->call('auth_userkey_request_login_url', [
            'user' => [
                'username' => $username,
                'password' => $password,
            ]
        ]);
    }

    /**
     * Obtener usuario por username (método estándar y confiable)
     */
    public function getUserByUsername($username)
    {
        try {
            \Log::info("Llamando a Moodle API para usuario: " . $username);
            
            $result = $this->call('core_user_get_users_by_field', [
                'field' => 'username',
                'values[0]' => $username
            ]);

            \Log::info("Respuesta de Moodle API:", $result);
            
            return $result;

        } catch (\Exception $e) {
            \Log::error("Error en getUserByUsername: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

}