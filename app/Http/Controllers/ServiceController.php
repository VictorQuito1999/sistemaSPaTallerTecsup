<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Service::all());
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'type' => 'required|string|max:50',
            'base_duration_min' => 'required|integer|min:1',
            'base_price' => 'required|numeric|min:0',
            'allows_double_booking' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $service = Service::create($data);

        return response()->json($service, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service)
    {
        return response()->json($service);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Service $service)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:150',
            'type' => 'sometimes|required|string|max:50',
            'base_duration_min' => 'sometimes|required|integer|min:1',
            'base_price' => 'sometimes|required|numeric|min:0',
            'allows_double_booking' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $service->update($data);

        return response()->json($service);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service)
    {
        $service->delete();
        return response()->json(null, 204);
    }

}
