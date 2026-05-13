<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EmployeeAuthController extends Controller
{
    private const STAFF_ROLES = ['groomer', 'receptionist'];

    public function login(Request $request): Response
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password ?? '')) {
            return response()->json(['message' => __('Credenciales inválidas.')], 422);
        }

        if (! in_array($user->role, self::STAFF_ROLES, true)) {
            return response()->json(['message' => __('Este acceso es solo para personal del spa.')], 422);
        }

        if (! $user->is_active) {
            return response()->json(['message' => __('Tu cuenta está inactiva. Activa el enlace enviado por correo.')], 422);
        }

        if ($user->must_change_password) {
            return response()->json([
                'message' => __('Debes completar la activación de cuenta.'),
                'must_change_password' => true,
                'user' => $this->publicStaffUser($user),
            ], 422);
        }

        $user->tokens()->delete();
        $tokenName = Str::slug('staff-'.$user->id.'-sanctum', '-');

        return response()->json([
            'message' => __('Login exitoso.'),
            'token' => $user->createToken($tokenName)->plainTextToken,
            'user' => $this->publicStaffUser($user),
        ]);
    }

    /** @return array<string, mixed> */
    private function publicStaffUser(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
        ];
    }
}
