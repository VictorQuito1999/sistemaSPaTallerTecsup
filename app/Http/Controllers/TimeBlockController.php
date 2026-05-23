<?php

namespace App\Http\Controllers;

use App\Models\TimeBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimeBlockController extends Controller
{
    public function index(): JsonResponse
    {
        $blocks = TimeBlock::with(['employee.user'])
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json($blocks);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'reason' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'employee_id' => 'nullable|exists:employees,id',
            'is_global' => 'required|boolean',
        ]);

        if ($data['is_global']) {
            $data['employee_id'] = null;
        }

        $block = TimeBlock::create($data);

        return response()->json($block->load('employee.user'), 201);
    }

    public function destroy(TimeBlock $timeBlock): JsonResponse
    {
        $timeBlock->delete();
        return response()->json(null, 204);
    }
}
