<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Http\Requests\DepartmentRequest;
use Symfony\Component\HttpFoundation\Response;

class DepartmentController extends Controller
{
  
    public function getDepartments()
    {
        $departments = Department::all();

        return response()->json($departments, Response::HTTP_OK);
    }

    
    public function createDepartment(DepartmentRequest $request)
    {
        $department = Department::create($request->validated());

        return response()->json([
            'message' => 'Departamento creado correctamente',
            'department' => $department
        ], Response::HTTP_CREATED);
    }
}
