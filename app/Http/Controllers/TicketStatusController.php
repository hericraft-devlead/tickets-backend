<?php

namespace App\Http\Controllers;

use App\Models\TicketStatus;
use Symfony\Component\HttpFoundation\Response;

class TicketStatusController extends Controller
{
    public function getStatuses()
    {
        return response()->json(
            TicketStatus::all(),
            Response::HTTP_OK
        );
    }
}
