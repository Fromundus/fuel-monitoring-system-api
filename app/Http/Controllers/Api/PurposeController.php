<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Purpose;
use App\Services\BroadcastEventService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurposeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = $request->query('per_page', 10);

        $query = Purpose::query()->with("requests");

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('account_code', 'like', "%{$search}%");
            });
        }

        $purposes = $query->orderBy('updated_at', 'desc')->paginate($perPage);

        // $purposes = Purpose::all();
        return response()->json([
            "purposes" => $purposes,
        ], 200);
    }

    public function allPurposes()
    {
        $purposes = Purpose::with('requests')->get();
        return response()->json($purposes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_code' => 'required|string|max:255|unique:purposes,account_code',
            'name' => 'required|string|max:255|unique:purposes,name',
        ]);

        $purpose = Purpose::create($validated);

        BroadcastEventService::signal("purpose");

        return response()->json([
            'message' => 'Purpose created successfully.',
            'data' => $purpose,
        ], 201);
    }

    public function update(Request $request, Purpose $purpose)
    {
        // $validated = $request->validate([
        //     'account_code' => 'required|string|max:255|unique:purposes,account_code',
        //     'name' => 'required|string|max:255|unique:purposes,name',
        // ]);
        
        $validated = $request->validate([
            'account_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('purposes', 'account_code')->ignore($purpose->id),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('purposes', 'name')->ignore($purpose->id),
            ],
        ]);

        $purpose->update($validated);

        BroadcastEventService::signal(signal: "purpose");

        return response()->json([
            'message' => 'Purpose updated successfully.',
            'data' => $purpose,
        ]);
    }

    public function destroy(Purpose $purpose)
    {
        $purpose->delete();

        BroadcastEventService::signal("purpose");

        return response()->json([
            'message' => 'Purpose deleted successfully.',
        ]);
    }
}
