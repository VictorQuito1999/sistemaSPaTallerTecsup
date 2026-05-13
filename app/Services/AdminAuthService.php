<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FAQRCode\Google2FA;
use PragmaRX\Google2FAQRCode\QRCode\Bacon;

class AdminAuthService
{
    protected const LOGIN_PURPOSE_SETUP = 'admin_2fa_setup';

    protected const LOGIN_PURPOSE_CHALLENGE = 'admin_2fa_challenge';

    public function attemptCredentialStep(string $email, string $password): array
    {
        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        if (! $user || $user->role !== 'admin') {
            return [
                'type' => 'error',
                'error_code' => 'invalid_credentials',
                'message' => __('Credenciales inválidas.'),
            ];
        }

        if ($user->locked_until && Carbon::now()->lessThan($user->locked_until)) {
            return [
                'type' => 'error',
                'error_code' => 'account_locked',
                'locked_until' => $user->locked_until,
                'message' => __('Cuenta bloqueada temporalmente. Intenta nuevamente en :minutes minutos.', [
                    'minutes' => Carbon::now()->diffInMinutes($user->locked_until) + 1,
                ]),
            ];
        }

        if (! Hash::check($password, $user->password ?? '')) {
            $user->increment('failed_login_attempts');
            if ($user->failed_login_attempts >= 5) {
                $user->locked_until = Carbon::now()->addMinutes(15);
                $user->failed_login_attempts = 5;
                $user->save();

                return [
                    'type' => 'error',
                    'error_code' => 'account_locked',
                    'failed_login_attempts' => (int) $user->failed_login_attempts,
                    'locked_until' => $user->locked_until,
                    'message' => __('Demasiados intentos fallidos. Cuenta bloqueada por 15 minutos.'),
                ];
            }

            $user->save();

            return [
                'type' => 'error',
                'error_code' => 'invalid_credentials',
                'failed_login_attempts' => (int) $user->failed_login_attempts,
                'message' => __('Credenciales inválidas.'),
            ];
        }

        $user->failed_login_attempts = 0;
        $user->locked_until = null;
        $user->save();

        if ($user->must_change_password) {
            return [
                'type' => 'must_change_password',
                'user' => $user,
            ];
        }

        if ($user->google2fa_secret) {
            return [
                'type' => 'two_factor_challenge',
                'pending_two_factor_token' => $this->makePendingToken([
                    'sub' => $user->id,
                    'purpose' => self::LOGIN_PURPOSE_CHALLENGE,
                    'exp' => Carbon::now()->addMinutes(15)->unix(),
                ]),
            ];
        }

        $google2fa = new Google2FA;
        $google2fa->setQrCodeService(new Bacon(new SvgImageBackEnd()));
        $secretKey = $google2fa->generateSecretKey();

        return [
            'type' => 'two_factor_setup',
            'pending_two_factor_token' => $this->makePendingToken([
                'sub' => $user->id,
                'purpose' => self::LOGIN_PURPOSE_SETUP,
                'sec' => $secretKey,
                'exp' => Carbon::now()->addMinutes(15)->unix(),
            ]),
            'google2fa_secret' => $secretKey,
            'qr_svg_inline' => $google2fa->getQRCodeInline(
                config('app.name', 'PetSpa'),
                $user->email,
                $secretKey
            ),
            'otpauth_url' => $google2fa->getQRCodeUrl(
                config('app.name', 'PetSpa'),
                $user->email,
                $secretKey
            ),
        ];
    }

    /**
     * @return array{type: string, token?: string, user?: User, message?: string}
     */
    public function confirmTwoFactorSetup(string $pendingTwoFactorToken, string $otp): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($pendingTwoFactorToken), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException | DecryptException) {
            return ['type' => 'error', 'message' => __('Token inválido o expirado.')];
        }

        if (($payload['purpose'] ?? '') !== self::LOGIN_PURPOSE_SETUP || ! isset($payload['sec'])) {
            return ['type' => 'error', 'message' => __('Token inválido.')];
        }

        if (($payload['exp'] ?? 0) < Carbon::now()->unix()) {
            return ['type' => 'error', 'message' => __('Token expirado.')];
        }

        $google2fa = new Google2FA;

        if (! $google2fa->verifyKey($payload['sec'], $otp)) {
            return ['type' => 'error', 'message' => __('Código 2FA inválido.')];
        }

        $userId = $payload['sub'];
        /** @var User|null $user */
        $user = User::find($userId);

        if (! $user || $user->role !== 'admin') {
            return ['type' => 'error', 'message' => __('Usuario no encontrado.')];
        }

        if ($user->google2fa_secret) {
            return ['type' => 'error', 'message' => __('Google 2FA ya está configurado para este usuario.')];
        }

        $user->google2fa_secret = $payload['sec'];
        $user->login_2fa_enabled = true;
        $user->save();

        $user->tokens()->delete();

        $tokenName = Str::slug('admin-'.$user->id.'-sanctum', '-');

        return [
            'type' => 'success',
            'token' => $user->createToken($tokenName)->plainTextToken,
            'user' => $user,
        ];
    }

    /**
     * @return array{type: string, token?: string, user?: User, message?: string}
     */
    public function verifyTwoFactorChallenge(string $pendingTwoFactorToken, string $otp): array
    {
        try {
            $payload = json_decode(Crypt::decryptString($pendingTwoFactorToken), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException | DecryptException) {
            return ['type' => 'error', 'message' => __('Token inválido o expirado.')];
        }

        if (($payload['purpose'] ?? '') !== self::LOGIN_PURPOSE_CHALLENGE) {
            return ['type' => 'error', 'message' => __('Token inválido.')];
        }

        if (($payload['exp'] ?? 0) < Carbon::now()->unix()) {
            return ['type' => 'error', 'message' => __('Token expirado.')];
        }

        /** @var User|null $user */
        $user = User::find($payload['sub']);

        if (! $user || $user->role !== 'admin' || ! $user->google2fa_secret) {
            return ['type' => 'error', 'message' => __('Sesión inválida.')];
        }

        $google2fa = new Google2FA;

        if (! $google2fa->verifyKey($user->google2fa_secret, $otp)) {
            return ['type' => 'error', 'message' => __('Código 2FA inválido.')];
        }

        if (! $user->login_2fa_enabled) {
            $user->login_2fa_enabled = true;
            $user->save();
        }

        $user->tokens()->delete();
        $tokenName = Str::slug('admin-'.$user->id.'-sanctum', '-');

        return [
            'type' => 'success',
            'token' => $user->createToken($tokenName)->plainTextToken,
            'user' => $user,
        ];
    }

    private function makePendingToken(array $payload): string
    {
        return Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{type: User|array|string}
     */
    public static function sanitizeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'must_change_password' => (bool) $user->must_change_password,
            'login_2fa_enabled' => (bool) $user->login_2fa_enabled,
            'email_verified_at' => $user->email_verified_at,
        ];
    }
}
