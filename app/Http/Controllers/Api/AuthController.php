<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\User;
use App\Support\CrmPayload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = $credentials['username'];
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $user = User::query()->where($field, $login)->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid username or password.',
            ], 422);
        }

        if (! in_array($user->role, ['admin', 'telecaller'], true)) {
            return response()->json([
                'message' => 'This account cannot access the CRM app.',
            ], 403);
        }

        $token = ApiToken::issue($user);

        return response()->json([
            'token' => $token,
            'user' => CrmPayload::user($user),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => CrmPayload::user($request->user()),
        ]);
    }

    public function logout(Request $request)
    {
        ApiToken::findByPlainToken($request->bearerToken())?->delete();

        return response()->json([
            'ok' => true,
        ]);
    }
}
