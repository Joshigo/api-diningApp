<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        if (auth()->attempt($credentials)) {
            $user = auth()->user();
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => $user,
                'token' => $token,
            ], 'Inicio de sesión exitoso.');
        }

        return $this->errorResponse('Credenciales inválidas.', 401);
    }
    public function logout(Request $request)
    {
        auth()->user()->tokens()->delete();
        return $this->successResponse(null, 'Sesión cerrada exitosamente.');
    }

    public function me(Request $request)
    {
        return $this->successResponse(auth()->user(), 'Usuario autenticado.');
    }


}
