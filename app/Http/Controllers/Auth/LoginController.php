<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginUserRequest;
use App\Models\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function login(LoginUserRequest $request): JsonResponse
    {

        // Попытка авторизации
        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();

            // Переносим корзину из сессии в базу данных (если есть данные в сессии)
            if (Session::has('cart') && count(Session::get('cart')) > 0) {
                $sessionCart = Session::get('cart');
                foreach ($sessionCart as $productId => $quantity) {
                    // Проверяем, есть ли уже такой товар в корзине пользователя
                    $cartItem = Cart::where('user_id', $user->id)->where('product_id', $productId)->first();

                    if ($cartItem) {
                        // Если товар уже есть в корзине, обновляем количество
                        $cartItem->quantity += $quantity;
                        $cartItem->save();
                    } else {
                        // Если товара нет в корзине, создаем новую запись
                        $cartItem = Cart::create([
                            'user_id' => $user->id,
                            'product_id' => $productId,
                            'quantity' => $quantity,
                        ]);
                    }
                }

                // Очищаем корзину в сессии после переноса в базу данных
                Session::forget('cart');
            }

            // Создаем токен для пользователя
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'message' => 'Авторизация успешна',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->isAdmin(),
                ],
                'redirect_to' => $user->isAdmin() ? '/admin/dashboard' : '/user/account',
            ]);
        }

        return response()->json(['message' => 'Неверный email или пароль'], Response::HTTP_NOT_FOUND);
    }

    public function logout(Request $request)
    {
        try {
            // Получаем чистый токен без префикса
            $tokenId = explode('|', $request->bearerToken())[0];

            // Удаляем токен напрямую через DB
            DB::table('personal_access_tokens')->where('id', $tokenId)->delete();

            // Очищаем сессию
            $request->session()->invalidate();

            return response()->json(['message' => 'Вы успешно вышли']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ошибка выхода',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
