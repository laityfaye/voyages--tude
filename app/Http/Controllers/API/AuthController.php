<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $utilisateur = User::where('email', $request->email)->first();

        if (!$utilisateur || !Hash::check($request->password, $utilisateur->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les informations d\'identification fournies sont incorrectes.'],
            ]);
        }

        return response()->json([
            'token' => $utilisateur->createToken('auth_token')->plainTextToken,
            'user' => $utilisateur->load('role')
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté avec succès']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user()->load('role'));
    }
}