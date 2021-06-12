<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller {

    public function register(Request $request) {
        $attr = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $attr['name'],
            'password' => bcrypt($attr['password']),
            'email' => $attr['email'],
        ]);

        return response()->json([
            'data' => [
                'token' =>  $user->createToken('API Token')->plainTextToken,
            ]
		]);
    }

    public function login(Request $request) {
        $attr = $request->validate([
            'email' => 'required|string|email|',
            'password' => 'required|string|min:6',
        ]);

        if (!Auth::attempt($attr)) {
            return $this->error('Credentials not match', 401);
        }

        return response()->json([
            'data' => [
                'token' => auth()->user()->createToken('API Token')->plainTextToken,
            ]
		]);
    }

    public function logout() {
        auth()->user()->tokens()->delete();

        return [
            'message' => 'Tokens Revoked',
        ];
    }

}
