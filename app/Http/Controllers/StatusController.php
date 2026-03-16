<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\JsonResponse;

class StatusController extends Controller
{
    /**
     * Display a listing of the resource for dropdowns.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        // Return only the fields needed for a dropdown to keep the payload small
        $statuses = Status::select('id', 'Status_Name')->orderBy('Status_Name')->get();
        return response()->json($statuses);
    }
}
