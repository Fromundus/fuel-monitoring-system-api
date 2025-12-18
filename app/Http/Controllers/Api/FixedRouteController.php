<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FixedRoute;
use App\Models\FixedRouteGroup;
use App\Models\FixedRouteRow;
use App\Services\BroadcastEventService;
use App\Services\RouteDistanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FixedRouteController extends Controller
{
    public function __construct(
        protected RouteDistanceService $distanceService
    ) {}

    // public function index(){
    //     $routes = FixedRoute::with('rows')->get();
    //     return response()->json($routes);
    // }

    // public function index(Request $request)
    // {
    //     $search = $request->query('search');

    //     $routes = FixedRoute::with('rows')
    //         ->when($search, function ($query, $search) {
    //             $query->where(function ($q) use ($search) {

    //                 // Search in route name
    //                 $q->where('name', 'LIKE', "%{$search}%")

    //                 // Or search inside rows
    //                 ->orWhereHas('rows', function ($r) use ($search) {
    //                     $r->where('departure', 'LIKE', "%{$search}%")
    //                         ->orWhere('destination', 'LIKE', "%{$search}%");
    //                 });

    //             });
    //         })
    //         ->get();

    //     return response()->json($routes);
    // }

    public function index(Request $request)
    {
        $search = $request->query('search');

        $routes = FixedRoute::with('groups.rows')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {

                    // Route name
                    $q->where('name', 'LIKE', "%{$search}%")

                    // Group name
                    ->orWhereHas('groups', function ($g) use ($search) {
                        $g->where('name', 'LIKE', "%{$search}%")

                        // Rows inside groups
                        ->orWhereHas('rows', function ($r) use ($search) {
                            $r->where('departure', 'LIKE', "%{$search}%")
                                ->orWhere('destination', 'LIKE', "%{$search}%");
                        });
                    });

                });
            })
            ->get();

        return response()->json($routes);
    }

    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         "name"                             => "required|unique:fixed_routes,name",
    //         "quantity"                         => "nullable|numeric|min:1",
    //         "groups"                           => "required|array|min:1",
    //         "groups.*.name"                    => "required|string",
    //         "groups.*.routes"                  => "required|array|min:1",
    //         "groups.*.routes.*.departure"      => "required|string",
    //         "groups.*.routes.*.destination"    => "required|string",
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         $distanceResult = $this->distanceService->calculateForGroups(
    //             $validated['groups'],
    //         );

    //         $route = FixedRoute::create([
    //             'name' => $validated['name'],
    //             'quantity' => $validated['quantity'],
    //             'distance' => $distanceResult['total_distance'],
    //         ]);
            
    //         foreach ($validated['groups'] as $groupData) {
    //             $group = $route->groups()->create([
    //                 'name' => $groupData['name'],
    //             ]);

    //             foreach ($groupData['routes'] as $row) {
    //                 $group->rows()->create([
    //                     'departure' => $row['departure'],
    //                     'destination' => $row['destination'],
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Route Created Successfully'
    //         ], 200);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         throw $e;
    //     }
    // }

    public function store(Request $request)
    {
        $validated = $request->validate([
            "name"                 => "required|unique:fixed_routes,name",
            "quantity"             => "nullable|sometimes|numeric|min:1",
            "groups"               => "required|array|min:1",
            "groups.*.name"        => "required|string",
            "groups.*.routes"      => "required|array|min:1",
            "groups.*.routes.*.departure"   => "required|string",
            "groups.*.routes.*.destination" => "required|string",
        ]);

        DB::beginTransaction();

        try {
            $route = FixedRoute::create([
                'name' => $validated['name'],
                'quantity' => $validated['quantity'],
                'distance' => 0, // temp
            ]);

            $grandTotalDistance = 0;

            foreach ($validated['groups'] as $groupData) {

                $group = FixedRouteGroup::create([
                    'fixed_route_id' => $route->id,
                    'name' => $groupData['name'],
                ]);

                $calculation = $this->distanceService
                    ->calculateDistance($groupData['routes']);

                $grandTotalDistance += $calculation['total_distance'];

                foreach ($calculation['rows'] as $row) {
                    FixedRouteRow::create([
                        'fixed_route_group_id' => $group->id,
                        'departure' => $row['departure'],
                        'destination' => $row['destination'],
                        'distance' => $row['distance'],
                    ]);
                }
            }

            // save total route distance
            $route->update([
                'distance' => $grandTotalDistance,
            ]);

            BroadcastEventService::signal(signal: "route");

            DB::commit();

            return response()->json([
                'message' => 'Route Created Successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function show($id)
    {
        $route = FixedRoute::with('groups.rows')->findOrFail($id);
        return response()->json($route);
    }

    /**
     * Update a route + its rows
     */
    // public function update(Request $request, $id)
    // {
    //     $validated = $request->validate([
    //         "name"                         => [
    //             'required',
    //             Rule::unique('fixed_routes', 'name')->ignore($id),
    //         ],
    //         "quantity"                     => "required|numeric|min:0",
    //         "routes"                       => "required|array|min:1",
    //         "routes.*.departure"           => "required|string",
    //         "routes.*.destination"         => "required|string",
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         $route = FixedRoute::findOrFail($id);

    //         // Update parent fields
    //         $route->update([
    //             'name' => $validated["name"],
    //             'quantity' => $validated["quantity"],
    //         ]);

    //         // Delete old rows
    //         FixedRouteRow::where("fixed_route_id", $route->id)->delete();

    //         // Insert new rows
    //         foreach ($validated["routes"] as $item) {
    //             FixedRouteRow::create([
    //                 "fixed_route_id" => $route->id,
    //                 "departure"      => $item["departure"],
    //                 "destination"    => $item["destination"],
    //             ]);
    //         }

    //         DB::commit();

    //         return response()->json([
    //             "message" => "Route Updated Successfully",
    //         ], 200);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         throw $e;
    //     }
    // }

    // public function update(Request $request, $id)
    // {
    //     $validated = $request->validate([
    //         "name" => [
    //             'required',
    //             Rule::unique('fixed_routes', 'name')->ignore($id),
    //         ],
    //         "quantity"                         => "nullable|numeric|min:1",
    //         "groups"                           => "required|array|min:1",
    //         "groups.*.name"                    => "required|string",
    //         "groups.*.rows"                  => "required|array|min:1",
    //         "groups.*.rows.*.departure"      => "required|string",
    //         "groups.*.rows.*.destination"    => "required|string",
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         $route = FixedRoute::findOrFail($id);

    //         $route->update([
    //             'name' => $validated['name'],
    //             'quantity' => $validated['quantity'],
    //         ]);

    //         // Delete old groups + rows
    //         foreach ($route->groups as $group) {
    //             $group->rows()->delete();
    //         }
    //         $route->groups()->delete();

    //         // Re-create
    //         foreach ($validated['groups'] as $groupData) {
    //             $group = $route->groups()->create([
    //                 'name' => $groupData['name'],
    //             ]);

    //             foreach ($groupData['rows'] as $row) {
    //                 $group->rows()->create([
    //                     'departure' => $row['departure'],
    //                     'destination' => $row['destination'],
    //                 ]);
    //             }
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Route Updated Successfully'
    //         ], 200);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         throw $e;
    //     }
    // }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            "name" => [
                'required',
                Rule::unique('fixed_routes', 'name')->ignore($id),
            ],
            "quantity"             => "nullable|sometimes|numeric|min:1",
            "groups"               => "required|array|min:1",
            "groups.*.name"        => "required|string",
            "groups.*.rows"      => "required|array|min:1",
            "groups.*.rows.*.departure"   => "required|string",
            "groups.*.rows.*.destination" => "required|string",
        ]);

        DB::beginTransaction();

        try {
            $route = FixedRoute::with('groups.rows')->findOrFail($id);

            // Update base fields
            $route->update([
                'name' => $validated['name'],
                'quantity' => $validated['quantity'],
            ]);

            // Remove old data
            foreach ($route->groups as $group) {
                $group->rows()->delete();
            }
            $route->groups()->delete();

            $grandTotalDistance = 0;

            // Recreate with recalculated distances
            foreach ($validated['groups'] as $groupData) {

                $group = $route->groups()->create([
                    'name' => $groupData['name'],
                ]);

                $calculation = $this->distanceService
                    ->calculateDistance($groupData['rows']);

                $grandTotalDistance += $calculation['total_distance'];

                foreach ($calculation['rows'] as $row) {
                    $group->rows()->create([
                        'departure'   => $row['departure'],
                        'destination' => $row['destination'],
                        'distance'    => $row['distance'], // ✅ saved
                    ]);
                }
            }

            // Update total route distance
            $route->update([
                'distance' => $grandTotalDistance,
            ]);

            BroadcastEventService::signal(signal: "route");

            DB::commit();

            return response()->json([
                'message' => 'Route Updated Successfully',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }



    /**
     * Delete a route and its rows
     */
    // public function destroy($id)
    // {
    //     $route = FixedRoute::findOrFail($id);

    //     try {
    //         DB::beginTransaction();

    //         // Delete related rows first
    //         FixedRouteRow::where("fixed_route_id", $route->id)->delete();

    //         // Delete parent
    //         $route->delete();

    //         DB::commit();

    //         return response()->json([
    //             "message" => "Route Deleted Successfully"
    //         ], 200);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         throw $e;
    //     }
    // }

    public function destroy($id)
    {
        $route = FixedRoute::findOrFail($id);

        try {
            DB::beginTransaction();

            foreach ($route->groups as $group) {
                $group->rows()->delete();
            }

            $route->groups()->delete();
            $route->delete();

            BroadcastEventService::signal(signal: "route");

            DB::commit();

            return response()->json([
                'message' => 'Route Deleted Successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
