<?php

namespace App\Http\Controllers;

use App\Models\Priority;
use Symfony\Component\HttpFoundation\Response;

class PriorityController extends Controller
{
    public function getPriorities()
    {
        return response()->json(
            Priority::orderBy('level')->get(),
            Response::HTTP_OK
        );
    }

}
