<?php

namespace App\Http\Controllers;

use App\Models\Breeds;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class BreedsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Breeds::with('category')->get());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => 'required|exists:species_categories,id',
            'name' => 'required|string|max:100',
            'size' => 'sometimes|required|in:extra_small,small,medium,large,extra_large',
            'duration_factor' => 'sometimes|required|numeric|min:0.1|max:9.99',
        ]);


        if (!isset($data['size'])) $data['size'] = 'medium';

        $breed = Breeds::create($data);

        return response()->json($breed->load('category'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Breeds $breed): JsonResponse
    {
        $breed->load('category');
        return response()->json($breed);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Breeds $breed): JsonResponse
    {
        $data = $request->validate([
            'category_id' => 'sometimes|required|exists:species_categories,id',
            'name' => 'sometimes|required|string|max:100',
            'size' => 'sometimes|required|in:extra_small,small,medium,large,extra_large',
            'duration_factor' => 'sometimes|required|numeric|min:0.1|max:9.99',
        ]);


        $breed->update($data);

        return response()->json($breed->load('category'));
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Breeds $breed): JsonResponse
    {
        $breed->delete();
        return response()->json(null, 204);
    }
}

