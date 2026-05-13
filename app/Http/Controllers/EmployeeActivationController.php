<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\EmployeeService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmployeeActivationController extends Controller
{
    public function complete(Request $request, EmployeeService $employeeService)
    {
        $validated = $request->validate([
            'user_id' => ['required_without:id', 'integer', 'exists:users,id'],
            'id' => ['required_without:user_id', 'integer', 'exists:users,id'],
            'expires' => ['required'],
            'signature' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
        ]);

        /** @var User $user */
        $user = User::findOrFail($validated['user_id'] ?? $validated['id']);

        $checkUrl = route('employee.activation', ['user' => $user->id], true)
            .'?expires='.urlencode((string) $validated['expires'])
            .'&signature='.urlencode($validated['signature']);

        $incoming = Request::create($checkUrl, 'GET');

        if (! $incoming->hasValidSignature()) {
            return response()->json(['message' => __('Enlace inválido o expirado.')], 422);
        }

        try {
            $employeeService->completeActivation($user, $validated['password']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $user->refresh();

        AuditLogger::write(
            $user->id.'/'.$user->role,
            $request,
            'employee.account_activation_completed'
        );

        $user->tokens()->delete();
        $tokenName = Str::slug('staff-'.$user->id.'-sanctum', '-');

        return response()->json([
            'message' => __('Cuenta activada. Sesión iniciada.'),
            'token' => $user->createToken($tokenName)->plainTextToken,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
            'redirect' => '/empleado/dashboard',
        ]);
    }
}
