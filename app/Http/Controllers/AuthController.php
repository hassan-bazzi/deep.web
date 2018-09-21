<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\JWTException;

use JWTAuth, Hash;

use App\User;

class AuthController extends Controller
{
    /**
     * Register a user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        Log::debug()
        $validatedData = $request->validate([
            'email' => 'required|email|unique:users,email|max:255',
            'name' => 'required|max:255'
        ]);

        $user = User::create ([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password'))
        ]);

        try {
            if (! $token = JWTAuth::attempt([
                'email' => $request->input('email'),
                'password' => $request->input('password')
            ])) {
                return response()->json(['success' => false, 'error' => 'We cannot find an account with the provided credentials.'], 404);
            }
        } catch (JWTException $e) {
            return response()->json(['success' => false, 'error' => 'Failed to login, please try again.'], 500);
        }

        return response()->json(['success' => true, 'data'=> [ 'token' => $token ]], 200);

    }

    /**
     * Log out
     * Invalidate the token, so user cannot use it anymore
     * They have to relogin to get a new token
     *
     * @param Request $request
     */
    public function logout(Request $request) {
        $this->validate($request, ['token' => 'required']);

        try {
            JWTAuth::invalidate($request->input('token'));
            return response()->json(['success' => true, 'message'=> "You have successfully logged out."]);
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['success' => false, 'error' => 'Failed to logout, please try again.'], 500);
        }
    }
}
