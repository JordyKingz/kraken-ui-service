<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Display the specified resource.
     *
     * @param $session_token
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($session_token)
    {
        $user = User::where('session_token', $session_token)->with('payments')->first();

        if ($user === null) {
            return response()->json([
                'message' => "No user found",
            ], 404);
        }

        return response()->json([
            'user' => $user
        ], 200);
    }
}
