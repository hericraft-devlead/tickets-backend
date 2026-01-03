<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\CategoryRequest;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends Controller
{
    public function getCategories()
    {
        return response()->json(
            Category::with('department')->get(),
            Response::HTTP_OK
        );
    }

    public function createCategory(CategoryRequest $request)
    {
        $category = Category::create($request->validated());

        return response()->json([
            'message' => 'Categoría creada correctamente',
            'category' => $category
        ], Response::HTTP_CREATED);
    }
}
