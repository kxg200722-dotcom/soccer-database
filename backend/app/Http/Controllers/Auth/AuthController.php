<?php

namespace App\Http\Controllers\Auth;

use App\Enums\ResponseCode;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Result;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return Result::error(ResponseCode::CREDENTIALS_INVALID)->toResponse();
        }

        $user->tokens()->delete();
        $token = $user->createToken('admin')->plainTextToken;

        return Result::ok([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'permissions' => $user->permissions ?? [],
            ],
        ])->toResponse();
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return Result::ok()->toResponse();
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return Result::ok([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => $user->is_admin,
            'permissions' => $user->permissions ?? [],
        ])->toResponse();
    }
}
