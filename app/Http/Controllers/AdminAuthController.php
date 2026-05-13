<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminAuthService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthController extends Controller
{
    public function login(Request $request, AdminAuthService $adminAuth): Response
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $result = $adminAuth->attemptCredentialStep(
            $validated['email'],
            $validated['password']
        );

        if (($result['type'] ?? '') === 'error') {
            $this->writeFailedLoginAudit($request, $validated['email'], $result);

            return response()->json(['message' => $result['message'] ?? 'Error'], 422);
        }

        if (($result['type'] ?? '') === 'must_change_password') {
            $user = $result['user'];

            return response()->json([
                'message' => __('Debes cambiar tu contraseña.'),
                'must_change_password' => true,
                'user' => AdminAuthService::sanitizeUser($user),
            ]);
        }

        if (($result['type'] ?? '') === 'two_factor_setup') {
            return response()->json([
                'message' => __('Configura Google Authenticator para continuar.'),
                'requires_google2fa_setup' => true,
                'pending_two_factor_token' => $result['pending_two_factor_token'],
                'google2fa_secret' => $result['google2fa_secret'],
                'qr_svg_inline' => $result['qr_svg_inline'],
                'otpauth_url' => $result['otpauth_url'],
                'email' => $validated['email'],
            ]);
        }

        return response()->json([
            'message' => __('Ingresa tu código Google Authenticator.'),
            'requires_google2fa_challenge' => true,
            'pending_two_factor_token' => $result['pending_two_factor_token'],
            'email' => $validated['email'],
        ]);
    }

    /**
     * Registra eventos de intentos fallidos/bloqueo para visibilidad en auditoría.
     */
    private function writeFailedLoginAudit(Request $request, string $email, array $result): void
    {
        /** @var User|null $user */
        $user = User::where('email', $email)->first();
        $who = $user ? ($user->id.'/'.$user->role) : 'guest/anonymous';
        $errorCode = $result['error_code'] ?? null;
        $attempts = (int) ($result['failed_login_attempts'] ?? ($user?->failed_login_attempts ?? 0));
        $lockedUntil = $result['locked_until'] ?? $user?->locked_until;

        if ($errorCode === 'account_locked' && $lockedUntil) {
            $isNewLock = $attempts >= 5;
            $event = $isNewLock
                ? 'admin.login_blocked_after_5_failed_attempts'
                : 'admin.login_blocked_during_lock_window';

            AuditLogger::write(
                $who,
                $request,
                $event.'; email='.$email.'; attempts='.$attempts.'; lock_until='.$lockedUntil
            );

            return;
        }

        AuditLogger::write(
            $who,
            $request,
            'admin.login_failed_credentials; email='.$email.'; attempts='.$attempts
        );
    }

    public function confirmSetup(Request $request, AdminAuthService $adminAuth): Response
    {
        $validated = $request->validate([
            'pending_two_factor_token' => ['required', 'string'],
            'otp' => ['required', 'digits:6'],
        ]);

        $out = $adminAuth->confirmTwoFactorSetup(
            $validated['pending_two_factor_token'],
            $validated['otp']
        );

        if (($out['type'] ?? '') === 'error') {
            return response()->json(['message' => $out['message']], 422);
        }

        $user = $out['user'];

        return response()->json([
            'message' => __('Autenticación completada.'),
            'token' => $out['token'],
            'user' => AdminAuthService::sanitizeUser($user),
        ]);
    }

    public function verifyChallenge(Request $request, AdminAuthService $adminAuth): Response
    {
        $validated = $request->validate([
            'pending_two_factor_token' => ['required', 'string'],
            'otp' => ['required', 'digits:6'],
        ]);

        $out = $adminAuth->verifyTwoFactorChallenge(
            $validated['pending_two_factor_token'],
            $validated['otp']
        );

        if (($out['type'] ?? '') === 'error') {
            return response()->json(['message' => $out['message']], 422);
        }

        /** @var \App\Models\User $user */
        $user = $out['user'];

        return response()->json([
            'message' => __('Autenticación completada.'),
            'token' => $out['token'],
            'user' => AdminAuthService::sanitizeUser($user),
        ]);
    }
}
