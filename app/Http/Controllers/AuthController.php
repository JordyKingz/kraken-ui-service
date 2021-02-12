<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Sign in with phonenumber
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function signIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phonenumber' => ['required', 'string', 'max:12'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 400);
        }

        $user = User::where('phonenumber', $request->phonenumber)->first();

        $sign_in_code = Str::random(10);
        if ($user === null) {
            try {
                User::create([
                    'phonenumber' => $request->phonenumber,
                    'sign_in_code' => $sign_in_code,
                ]);
            } catch (\ErrorException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 400);
            }
        } else {
            try {
                $user->sign_in_code = $sign_in_code;
                $user->save();
            } catch (\ErrorException $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 400);
            }
        }

        // TODO Send SMS

        return response()->json([
            'message' => "Your sign in code is send.",
            'code' => $sign_in_code,
        ], 200);
    }

    /**
     * Validate user
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateSignIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'max:12'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
            ], 400);
        }

        $user = User::where('sign_in_code', $request->code)->first();

        if ($user === null) {
            return response()->json([
                'message' => "No user found",
            ], 404);
        }

        try {
            $user->session_token = Str::random(128);
            $user->updated_at = Carbon::now();
            $user->save();
        } catch (\ErrorException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }

        return response()->json([
            'user' => $user,
        ], 200);
    }
}
