<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Http\Requests\TagRequest;
use Symfony\Component\HttpFoundation\Response;

class TagController extends Controller
{
    public function getTags()
    {
        return response()->json(Tag::all(), Response::HTTP_OK);
    }

    public function createTag(TagRequest $request)
    {
        $tag = Tag::create($request->validated());

        return response()->json([
            'message' => 'Etiqueta creada correctamente',
            'tag' => $tag
        ], Response::HTTP_CREATED);
    }
}
