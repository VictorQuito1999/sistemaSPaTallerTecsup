<?php

namespace App\Http\Controllers;

use App\Models\species_categories;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class SpeciesCategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(species_categories::all());
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
            'name' => 'required|string|max:100',
        ]);

        $category = species_categories::create($data);

        return response()->json($category, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(species_categories $species_category): JsonResponse
    {
        return response()->json($species_category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, species_categories $species_category): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:100',
        ]);

        $species_category->update($data);

        return response()->json($species_category);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(species_categories $species_category): JsonResponse
    {
        $species_category->delete();
        return response()->json(null, 204);
    }
}

