<?php

namespace App\Http\Controllers;

use App\Models\User;

class EmployeeActivationWebController extends Controller
{
    /**
     * Redirige al SPA preservando firma/expiración para completar activación contra la API.
     */
    public function redirect(User $user)
    {
        if ($user->role !== 'groomer') {
            abort(403);
        }

        if ($user->is_active) {
            abort(403, __('La cuenta ya fue activada.'));
        }

        $base = rtrim((string) config('app.url'), '/');
        $params = array_merge(
            ['id' => (string) $user->id],
            request()->only(['expires', 'signature'])
        );
        $target = $base.'/empleado/activar?'.http_build_query($params);

        return redirect()->away($target);
    }
}
