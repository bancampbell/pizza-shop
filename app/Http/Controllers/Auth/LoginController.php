<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(LoginUserRequest $request): JsonResponse
    {

        // Попытка авторизации
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            return response()->json(['message' => 'Авторизация прошла успешно', 'user' => $user]);
        }

        return response()->json(['message' => 'Неверный email или пароль'], Response::HTTP_NOT_FOUND);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        return response()->json(['message' => 'Вы вышли из системы']);
    }
}
