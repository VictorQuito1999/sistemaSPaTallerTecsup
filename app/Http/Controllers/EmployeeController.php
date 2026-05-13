<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService
    ) {}

    public function index()
    {
        $rows = Employee::query()
            ->with('user')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Employee $employee) => $this->toArray($employee));

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'ci' => ['required', 'string', 'max:32', Rule::unique('employees', 'ci')],
            'specialty' => ['nullable', 'string', 'max:255'],
            'shift' => ['required', 'string', Rule::in(['mañana', 'tarde', 'noche'])],
        ]);

        $employee = $this->employeeService->create($validated);

        return response()->json($this->toArray($employee), 201);
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:30'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'shift' => ['sometimes', 'string', Rule::in(['mañana', 'tarde', 'noche'])],
        ]);

        $employee = $this->employeeService->update($employee, $validated);

        return response()->json($this->toArray($employee));
    }

    public function deactivate(Employee $employee)
    {
        $employee = $this->employeeService->deactivate($employee);

        return response()->json([
            'message' => __('Empleado marcado como inactivo.'),
            'data' => $this->toArray($employee),
        ]);
    }

    private function toArray(Employee $employee): array
    {
        $employee->loadMissing('user');

        $user = $employee->user;

        return [
            'id' => $employee->id,
            'ci' => $employee->ci,
            'shift' => $employee->shift,
            'specialty' => $employee->specialty,
            'calendar_color' => $employee->calendar_color,
            'concurrent_capacity' => $employee->concurrent_capacity,
            'user' => $user ? [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'is_active' => (bool) $user->is_active,
                'must_change_password' => (bool) $user->must_change_password,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ] : null,
        ];
    }
}
