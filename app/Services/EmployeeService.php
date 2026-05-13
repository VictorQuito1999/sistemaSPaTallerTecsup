<?php

namespace App\Services;

use App\Mail\EmployeeActivationMail;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class EmployeeService
{
    /**
     * @param  array{first_name: string, last_name?: ?string, email: string, phone: string, ci: string, specialty?: ?string, shift: string}  $attributes
     */
    public function create(array $attributes): Employee
    {
        $temporaryPasswordHash = Hash::make(Str::password(48));

        $user = User::create([
            'first_name' => $attributes['first_name'],
            'last_name' => $attributes['last_name'] ?? null,
            'email' => $attributes['email'],
            'phone' => $attributes['phone'],
            'password' => $temporaryPasswordHash,
            'role' => 'groomer',
            'must_change_password' => true,
            'is_active' => false,
            'email_verified_at' => null,
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'ci' => $attributes['ci'],
            'specialty' => $attributes['specialty'] ?? null,
            'shift' => $attributes['shift'],
        ]);

        // URL absoluta: usa APP_URL (config/app.php → env APP_URL)). Vigencia del firma: 15 minutos.
        $activationUrl = URL::temporarySignedRoute(
            'employee.activation',
            Carbon::now()->addMinutes(15),
            ['user' => $user->id],
            absolute: true
        );

        Mail::to($user->email)->send(new EmployeeActivationMail($activationUrl, $user->first_name ?? 'colaborador'));

        return $employee->load(['user']);
    }

    /**
     * @param  array{first_name?: string, last_name?: ?string, phone?: string, specialty?: ?string, shift?: string}  $attributes
     */
    public function update(Employee $employee, array $attributes): Employee
    {
        $user = $employee->user;

        if (! $user) {
            throw new ModelNotFoundException;
        }

        $user->fill(array_filter([
            'first_name' => $attributes['first_name'] ?? null,
            'last_name' => $attributes['last_name'] ?? null,
            'phone' => $attributes['phone'] ?? null,
        ], fn ($v) => $v !== null));

        $user->save();

        $employee->fill(array_filter([
            'specialty' => $attributes['specialty'] ?? null,
            'shift' => $attributes['shift'] ?? null,
        ], fn ($v) => $v !== null));

        $employee->save();

        return $employee->refresh()->load(['user']);
    }

    public function deactivate(Employee $employee): Employee
    {
        $user = $employee->user;

        if ($user) {
            $user->is_active = false;
            $user->save();
            $user->tokens()->delete();
        }

        return $employee->refresh()->load(['user']);
    }

    /**
     * @return array{user: User, employee: Employee}
     */
    public function completeActivation(User $user, string $password): array
    {
        if ($user->role !== 'groomer') {
            throw new \InvalidArgumentException(__('Usuario no válido para activación.'));
        }

        if ($user->is_active) {
            throw new \InvalidArgumentException(__('La cuenta ya está activa.'));
        }

        $user->password = $password;
        $user->must_change_password = false;
        $user->is_active = true;
        $user->email_verified_at = now();
        $user->password_changed_at = now();
        $user->save();

        $employee = $user->employee;

        if (! $employee) {
            throw new ModelNotFoundException;
        }

        return ['user' => $user->refresh(), 'employee' => $employee->refresh()];
    }
}
