<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public const CUSTOMER_TOKEN_NAME = 'customer-web';

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect('/cliente/login?oauth_error='.rawurlencode('No se pudo completar el inicio de sesión con Google.'));
        }

        $email = $googleUser->getEmail();
        if (! $email) {
            return redirect('/cliente/login?oauth_error='.rawurlencode('Tu cuenta de Google no tiene un correo disponible.'));
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $email)->first();
        }

        if ($user && $user->role !== 'customer') {
            return redirect('/cliente/login?oauth_error='.rawurlencode('Esta cuenta está registrada como personal o administración. Usa la página de acceso correspondiente.'));
        }

        if ($user) {
            if (! $user->google_id) {
                $user->google_id = $googleUser->getId();
                $user->save();
            }
        } else {
            $given = $googleUser->user['given_name'] ?? null;
            $family = $googleUser->user['family_name'] ?? null;
            $fullName = $googleUser->getName() ?: '';
            $parts = preg_split('/\s+/', trim($fullName), 2);
            $firstName = $given ?: (($parts[0] ?: '') !== '' ? $parts[0] : 'Cliente');
            $lastName = $family ?? ($parts[1] ?? '');

            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => null,
                'password' => null,
                'role' => 'customer',
                'google_id' => $googleUser->getId(),
                'email_verified_at' => now(),
                'must_change_password' => false,
                'otp_code' => null,
                'otp_expires_at' => null,
            ]);

            $this->createCustomerRecord($user->id);
        }

        if (! $user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }

        // Sesion unica para cliente.
        $user->tokens()->delete();
        $plainToken = $user->createToken(self::CUSTOMER_TOKEN_NAME)->plainTextToken;

        return redirect('/cliente/dashboard')->withFragment('token='.rawurlencode($plainToken));
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
}
