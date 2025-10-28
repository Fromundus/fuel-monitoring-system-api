<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Source;
use Illuminate\Http\Request;

class SourceController extends Controller
{
        /**
     * Display a listing of the sources.
     */
    public function index()
    {
        $sources = Source::all();
        return response()->json($sources);
    }

    /**
     * Store a newly created source.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sources,name',
        ]);

        $source = Source::create($validated);

        return response()->json([
            'message' => 'Source created successfully.',
            'data' => $source,
        ], 201);
    }

    /**
     * Update the specified source.
     */
    public function update(Request $request, Source $source)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $source->update($validated);

        return response()->json([
            'message' => 'Source updated successfully.',
            'data' => $source,
        ]);
    }

    /**
     * Remove the specified source.
     */
    public function destroy(Source $source)
    {
        $source->delete();

        return response()->json([
            'message' => 'Source deleted successfully.',
        ]);
    }
}
