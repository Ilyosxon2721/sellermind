<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'locale' => $request->locale ?? session('locale', 'ru'),
        ]);

        // Create API token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Log the user in for web session (only if session is available)
        if ($request->hasSession()) {
            Auth::login($user, true);
            $request->session()->regenerate();
            $request->session()->save(); // Force save session
        }

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            return response()->json([
                'message' => 'Неверный email или пароль.',
            ], 401);
        }

        $user = Auth::user();

        // Create API token
        $token = $user->createToken('auth-token')->plainTextToken;

        // Ensure web session is created and saved (only if session is available)
        if ($request->hasSession()) {
            // Auth::attempt already logged in, but let's ensure it's saved
            $request->session()->regenerate();
            $request->session()->save(); // Force save session
        }

        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        // Delete Sanctum token
        $request->user()->currentAccessToken()?->delete();

        // Also clear web session (only if session is available)
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Вы успешно вышли из системы.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()->load('companies')),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'locale' => ['nullable', 'string', 'in:ru,uz,en'],
        ]);

        $user = $request->user();
        $user->update($request->only(['name', 'locale']));

        if ($request->locale && $request->hasSession()) {
            $request->session()->put('locale', $request->locale);
            // Also explicitly set app locale for current response
            \App::setLocale($request->locale);
        }

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    public function updateLocale(Request $request): JsonResponse
    {
        $request->validate([
            'locale' => ['required', 'string', 'in:ru,uz,en'],
        ]);

        $user = $request->user();
        $user->update(['locale' => $request->locale]);

        // Also update session locale
        if ($request->hasSession()) {
            $request->session()->put('locale', $request->locale);
            \App::setLocale($request->locale);
        }

        return response()->json([
            'success' => true,
            'locale' => $request->locale,
            'user' => new UserResource($user),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Текущий пароль неверен.',
            ], 422);
        }

        $user->update([
            'password' => $request->password,
        ]);

        return response()->json([
            'message' => 'Пароль успешно изменён.',
        ]);
    }

    /**
     * Регистрация клиента из Risment с автоматическим созданием Company
     */
    public function registerClient(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'company_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        \DB::beginTransaction();
        try {
            // Создать компанию в Sellermind
            $company = \App\Models\Company::create([
                'name' => $validated['company_name'],
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'is_fulfillment_client' => true,
                'risment_subscription_plan' => 'basic',
                'subscription_expires_at' => now()->addDays(30), // 30 дней trial
            ]);

            // Создать пользователя
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'company_id' => $company->id,
            ]);

            \DB::commit();

            // Создать токен
            $token = $user->createToken('risment-client')->plainTextToken;

            return response()->json([
                'success' => true,
                'token' => $token,
                'user' => $user->only(['id', 'name', 'email']),
                'company' => $company->only(['id', 'name']),
            ], 201);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login для клиентов Risment
     */
    public function loginClient(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        $user = Auth::user();

        // Создать токен для Risment
        $token = $user->createToken('risment-client')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $user->only(['id', 'name', 'email']),
            'company' => $user->company ? $user->company->only(['id', 'name']) : null,
        ]);
    }
}
