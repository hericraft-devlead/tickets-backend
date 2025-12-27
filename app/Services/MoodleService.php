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
 
    public function customLogin($username, $password)
    {
        return $this->call('AppServiceLogin', [
            'username' => $username,
            'password' => $password,
        ]);
    }

    public function authenticateWithCustomService($username, $password)
    {
        $result = $this->customLogin($username, $password);
        
        if ($result['success']) {
            return $result;
        }
        
        return $result;
    }
    
    public function getUserByCredentialsCustom($username, $password)
    {
        $loginResult = $this->authenticateWithCustomService($username, $password);
        
        if (!$loginResult['success']) {
            return $loginResult;
        }

        return $this->call('core_user_get_users_by_field', [
            'field' => 'username',
            'values[0]' => $username
        ]);
    }

    public function getUserByCredentials($username, $password)
    {
        $authResult = $this->authenticateUser($username, $password);
        
        if (!$authResult['success']) {
            return $authResult;
        }

        return $this->call('core_user_get_users_by_field', [
            'field' => 'username',
            'values[0]' => $username
        ]);
    }

    public function authenticateUser($username, $password)
    {
        return $this->call('auth_userkey_request_login_url', [
            'user' => [
                'username' => $username,
                'password' => $password,
            ]
        ]);
    }

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

    public function getAllRoles()
    {
        return $this->call('core_role_get_roles');
    }


    public function getUserRoles($userId)
    {
        return $this->call('core_role_get_user_roles', [
            'userid' => $userId
        ]);
    }

    public function getUsersRoles($userIds, $contextId = null)
    {
        $userlist = [];
        foreach ((array)$userIds as $userId) {
            $userlist[] = ['userid' => $userId];
        }

        $params = [
            'userlist' => $userlist
        ];

        if ($contextId) {
            $params['contextid'] = $contextId;
        }

        return $this->call('core_role_get_users_roles', $params);
    }

    public function getUserRolesInCourse($userId, $courseId)
    {
        $contextResult = $this->call('core_role_get_context_roles', [
            'contextlevel' => 'course',
            'instanceid' => $courseId
        ]);

        if (!$contextResult['success']) {
            return $contextResult;
        }

        $contextId = $contextResult['data'][0]['contextid'] ?? null;
        
        if (!$contextId) {
            return [
                'success' => false,
                'error' => 'No se pudo obtener el contexto del curso'
            ];
        }

        // Luego obtener los roles del usuario en ese contexto
        return $this->getUsersRoles([$userId], $contextId);
    }

    /**
     * Obtener contextos de roles para un usuario
     */
    public function getRoleContexts($userId)
    {
        return $this->call('core_role_get_role_contexts', [
            'userid' => $userId
        ]);
    }

    /**
     * Obtener asignaciones de roles para un usuario
     */
    public function getRoleAssignments($userId, $contextLevel = null, $instanceId = null)
    {
        $params = ['userid' => $userId];
        
        if ($contextLevel) {
            $params['contextlevel'] = $contextLevel;
        }
        
        if ($instanceId) {
            $params['instanceid'] = $instanceId;
        }

        return $this->call('core_role_get_role_assignments', $params);
    }

    /**
     * Buscar usuarios por rol (método alternativo)
     */
    public function getUsersByRole($roleId, $contextLevel = null, $instanceId = null)
    {
        $params = ['roleid' => $roleId];
        
        if ($contextLevel) {
            $params['contextlevel'] = $contextLevel;
        }
        
        if ($instanceId) {
            $params['instanceid'] = $instanceId;
        }

        return $this->call('core_role_get_users_by_role', $params);
    }

    public function getAvailableFunctions()
    {
        return $this->call('core_webservice_get_site_info');
    }   
}