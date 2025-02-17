<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|max:255|email|unique:users,email',
                'password' => 'required|string|confirmed|min:4',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 403);
        }

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            $response = [
                'user' => $user,
                'access_token' => $token,
            ];

            return response()->json($response);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        if ($request->user()) {
            return response()->json(['message' => 'Already logged in'], 200);
        }

        try {
            $validated = $request->validate([
                'email' => 'required|string|max:255|email',
                'password' => 'required|string|min:4',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 403);
        }

        $credentials = request(['email', 'password']);
        try {
            if (!Auth::attempt($credentials)) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $user = User::where('email', $validated['email'])->firstOrFail();
            $token = $user->createToken('auth_token')->plainTextToken;
            $response = [
                'user' => $user,
                'access_token' => $token,
            ];

            return response()->json($response);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            try {
                $request->user()->tokens()->delete();
                return response()->json(['message' => 'Logged out'], 200);
            } catch (Exception $e) {
                return response()->json(['message' => $e->getMessage()], 500);
            }
        } else {
            return response()->json(['message' => 'Not authenticated'], 401);
        }
    }


}
