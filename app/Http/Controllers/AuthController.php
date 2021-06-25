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
            'profile_id' => \App\Models\Profile::first()->id,
            'name' => $attr['name'],
            'password' => bcrypt($attr['password']),
            'email' => $attr['email'],
        ]);

        if (!Auth::attempt($user)) {
            return $this->error('Credentials not match', 401);
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function login(Request $request) {
        $attr = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:6',
        ]);

        if (!Auth::attempt($attr)) {
            return $this->error('Credentials not match', 401);
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function token(Request $request) {
        $attr = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|min:6',
        ]);

        if (!Auth::attempt($attr)) {
            return $this->error('Credentials not match', 401);
        }

        $token = \Auth::user()->createToken('API TOKEN')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }

    public function logout() {
        \Auth::guard('web')->logout();

        return [
            'message' => 'Tokens Revoked',
        ];
    }

}
