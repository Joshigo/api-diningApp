<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\MockObject\Api;

class UserController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $users = User::Where('id' != 1)->get();
        return $this->successResponse($users, 'Lista de usuarios obtenida exitosamente.');
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = User::create($data);
        return $this->successResponse($user, 'Usuario creado exitosamente.', 201);
    }

    public function show($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse('Usuario no encontrado.', 404);
        }
        return $this->successResponse($user, 'Usuario obtenido exitosamente.');
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse('Usuario no encontrado.', 404);
        }

        $user->update($request->validated());
        return $this->successResponse($user, 'Usuario actualizado exitosamente.');
    }

    public function toggleStatus($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse('Usuario no encontrado.', 404);
        }

        $user->isActive = !$user->isActive;
        $user->save();

        return $this->successResponse($user, 'Estado del usuario actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->errorResponse('Usuario no encontrado.', 404);
        }

        $user->delete();
        return $this->successResponse(null, 'Usuario eliminado exitosamente.');
    }
}
