<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Illuminate\Routing\Controllers\HasMiddleware;


class RoleController extends Controller implements HasMiddleware
{
         // Middleware for Role and Permission Control
         public static function middleware(): array
         {
             return [
                 new Middleware('permission:create role', only: ['store']),
                 new Middleware('permission:view role', only: ['index']),
                 new Middleware('permission:view single role', only: ['show']),
                 new Middleware('permission:update role', only: ['update']),
                 new Middleware('permission:delete role', only: ['destroy']),
             ];
         }
    public function index()
    {
        $roles = Role::with(['permissions', 'users'])->get();

        if ($roles->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Roles not found',
                'data' => $roles // Empty collection
            ], 200, [], JSON_NUMERIC_CHECK);
        }
        // Add `role_id` to each user in the users array
        $roles->each(function ($role) {
            $role->users->each(function ($user) use ($role) {
                $user->role_id = $role->id;
            });
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Roles found',
            'data' => $roles
        ], 200, [], JSON_NUMERIC_CHECK);
    }

    public function show(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validatedData->fails()) {
            $errors = implode(' ', $validatedData->errors()->all());
            return response()->json([
                'status' => 'failed',
                'message' => $errors
            ], 422, [], JSON_NUMERIC_CHECK);
        }

        $role = Role::with(['permissions', 'users'])->find($request->role_id); // Eager load permissions

        if (!$role) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Role not found'
            ], 404, [], JSON_NUMERIC_CHECK);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Role found",
            'data' => $role
        ], 200, [], JSON_NUMERIC_CHECK);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            $errors = implode(' ', $validator->errors()->all());
            return response()->json([
                'status' => "failed",
                'message' => $errors
            ], 422);
        }

        $role = Role::create([
            'name' => $request->name,
            'created_by' => Auth::id(),
        ]);

        // Assign permissions to the newly created role
        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Role created successfully",
            'data' => $role,
        ], 200, [], JSON_NUMERIC_CHECK);
    }

    public function update(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'name' => 'required|string|max:255|unique:roles,name,' . $request->role_id,
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($validatedData->fails()) {
            $errors = implode(' ', $validatedData->errors()->all());
            return response()->json([
                'status' => 'failed',
                'message' => $errors
            ], 422, [], JSON_NUMERIC_CHECK);
        }

        $role = Role::find($request->role_id);

        if (!$role) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Role not found'
            ], 404, [], JSON_NUMERIC_CHECK);
        }

        $role->name = $request->name;
        $role->save();

        // Sync the permissions with the updated array
        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }
        $role->load('permissions');
        $role->makeHidden(['updated_at', 'created_at', 'guard_name']);

        return response()->json([
            'status' => 'success',
            'message' => "Changes saved successfully",
            'data' => $role
        ], 200, [], JSON_NUMERIC_CHECK);
    }

    public function destroy(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validatedData->fails()) {
            $errors = implode(' ', $validatedData->errors()->all());
            return response()->json([
                'status' => 'failed',
                'message' => $errors
            ], 422, [], JSON_NUMERIC_CHECK);
        }

        $role = Role::find($request->role_id);

        if (!$role) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Role not found'
            ], 404, [], JSON_NUMERIC_CHECK);
        }

        // Detach all permissions associated with this role (if any)
        $role->permissions()->detach();

        // Deleting the role
        $role->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted successfully'
        ], 200, [], JSON_NUMERIC_CHECK);
    }
}
