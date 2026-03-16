<?php

namespace App\Http\Controllers;

use App\Services\UserHistoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserHistoryController extends Controller
{
    protected $userHistoryService;

    public function __construct(UserHistoryService $userHistoryService)
    {
        $this->userHistoryService = $userHistoryService;
    }

    /**
     * Fetch previously owned items history.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $history = $this->userHistoryService->getHistory($user, $request);

        return response()->json($history);
    }
}