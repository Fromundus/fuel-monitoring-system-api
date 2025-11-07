<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\BroadcastEventService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionController extends Controller
{
    public function roles(){
        $roles = Role::all();

        return $roles;
    }

    public function permissions(){
        $permissions = Permission::all();

        return $permissions;
    }

    public function updateUserAccess(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $user = User::findOrFail($id);

        // Assign role (category only)
        $user->syncRoles([$request->role]);

        // Assign direct permissions (independent)
        $user->syncPermissions($request->permissions);

        // Mirror role string to user's table field
        $user->role = $request->role;
        $user->save();

        BroadcastEventService::signal('user');

        return response()->json(['message' => 'Access updated successfully.']);
    }

    public function access($id){
        $user = User::findOrFail($id);

        return response()->json(new UserResource($user));
    }
}
