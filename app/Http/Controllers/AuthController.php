<?php

namespace App\Http\Controllers;

use App\Mail\CustomerOtpMail;
use App\Models\Customer;
use App\Models\User;
use App\Rules\StrongPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function me(Request $request)
    {
        return response()->json($this->publicUser($request->user()));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesion cerrada correctamente.',
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', new StrongPassword],
        ]);

        $otpCode = $this->generateOtpCode();

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'role' => 'customer',
            'otp_code' => $otpCode,
            'otp_expires_at' => now()->addMinutes(10),
            'must_change_password' => false,
        ]);

        $this->createCustomerRecord($user->id);
        $this->sendCustomerOtp($user);

        return response()->json([
            'message' => 'Registro completado. Verifica tu correo con el OTP.',
            'requires_verification' => true,
            'user' => $this->publicUser($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user
            || $user->password === null
            || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas.'], 422);
        }

        if ($user->role === 'admin') {
            return response()->json(['message' => 'Los administradores deben iniciar sesión en /admin/login.'], 422);
        }

        if (in_array($user->role, ['groomer', 'receptionist'], true)) {
            return response()->json([
                'message' => 'El personal debe iniciar sesión en la página de empleados (/login).',
            ], 422);
        }

        if ($user->must_change_password) {
            return response()->json([
                'message' => 'Debes cambiar tu contraseña.',
                'must_change_password' => true,
                'user' => $this->publicUser($user),
            ]);
        }

        if (! $user->email_verified_at) {
            $user->otp_code = $this->generateOtpCode();
            $user->otp_expires_at = now()->addMinutes(10);
            $user->save();
            $this->sendCustomerOtp($user);

            return response()->json([
                'message' => 'Tu correo aún no está verificado.',
                'requires_verification' => true,
                'user' => $this->publicUser($user),
            ]);
        }

        $payload = [
            'message' => 'Login exitoso.',
            'user' => $this->publicUser($user),
        ];

        if ($user->role === 'customer') {
            $payload['token'] = $this->issueCustomerToken($user);
        }

        return response()->json($payload);
    }

    public function verifyEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'otp_code' => ['required', 'digits:6'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! $user->otp_code) {
            return response()->json(['message' => 'No hay OTP pendiente para este correo.'], 422);
        }

        if ($user->otp_code !== $validated['otp_code']) {
            return response()->json(['message' => 'Código OTP inválido.'], 422);
        }

        if (! $user->otp_expires_at || now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'Código OTP expirado.'], 422);
        }

        $user->email_verified_at = now();
        $user->otp_code = null;
        $user->otp_expires_at = null;
        $user->save();

        $payload = [
            'message' => 'Correo verificado correctamente.',
            'user' => $this->publicUser($user),
        ];

        if ($user->role === 'customer') {
            $payload['token'] = $this->issueCustomerToken($user);
        }

        return response()->json($payload);
    }

    public function resendOtp(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', Rule::exists('users', 'email')],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        $user->otp_code = $this->generateOtpCode();
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();
        $this->sendCustomerOtp($user);

        return response()->json([
            'message' => 'Nuevo OTP generado y enviado a tu correo.',
        ]);
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', Rule::exists('users', 'email')],
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();
        $user->password = $validated['password'];
        $user->must_change_password = false;
        $user->password_changed_at = now();
        $user->save();

        $payload = [
            'message' => 'Contraseña actualizada.',
            'user' => $this->publicUser($user),
        ];

        if ($user->role === 'customer') {
            $payload['token'] = $this->issueCustomerToken($user);
        }

        return response()->json($payload);
    }

    public function adminMetrics()
    {
        $appointmentsToday = 0;

        if (Schema::hasTable('appointments')) {
            $appointmentsToday = DB::table('appointments')
                ->whereDate('start_at', now()->toDateString())
                ->count();
        }

        $newCustomers = $this->countNewCustomers();

        $activeGroomers = User::query()
            ->where('role', 'groomer')
            ->where('is_active', true)
            ->count();

        return response()->json([
            'appointments_today' => $appointmentsToday,
            'new_customers' => $newCustomers,
            'active_groomers' => $activeGroomers,
        ]);
    }

    private function generateOtpCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function sendCustomerOtp(User $user): void
    {
        if ($user->role !== 'customer' || ! $user->otp_code) {
            return;
        }

        Mail::to($user->email)->send(new CustomerOtpMail(
            otpCode: $user->otp_code,
            customerName: $user->first_name ?? 'cliente',
        ));
    }

    private function issueCustomerToken(User $user): string
    {
        // Sesion unica para cliente: al emitir uno nuevo invalida los anteriores.
        $user->tokens()->delete();

        return $user->createToken(GoogleAuthController::CUSTOMER_TOKEN_NAME)->plainTextToken;
    }

    private function publicUser(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'must_change_password' => (bool) $user->must_change_password,
            'email_verified_at' => $user->email_verified_at,
        ];
    }

    private function createCustomerRecord(int $userId): void
    {
        if (Schema::hasTable('customers')) {
            Customer::create([
                'user_id' => $userId,
                'status' => 1,
            ]);

            return;
        }

        if (Schema::hasTable('clientes')) {
            DB::table('clientes')->insert([
                'user_id' => $userId,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function countNewCustomers(): int
    {
        if (Schema::hasTable('customers')) {
            return DB::table('customers')
                ->whereDate('created_at', now()->toDateString())
                ->count();
        }

        if (Schema::hasTable('clientes')) {
            return DB::table('clientes')
                ->whereDate('created_at', now()->toDateString())
                ->count();
        }

        return 0;
    }
}
