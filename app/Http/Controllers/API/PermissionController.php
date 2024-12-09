<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;


class PermissionController extends Controller implements HasMiddleware
{
     // Middleware for Role and Permission Control
     public static function middleware(): array
     {
         return [
             new Middleware('permission:create permission', only: ['store']),
             new Middleware('permission:view permission', only: ['index']),
             new Middleware('permission:view single permission', only: ['show']),
             new Middleware('permission:update permission', only: ['update']),
             new Middleware('permission:delete permission', only: ['destroy']),
         ];
     }
    public function index()
    {
        $permissions = Permission::all();

        if ($permissions->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Permissions not found',
                'data' => $permissions // Empty collection
            ], 200, [], JSON_NUMERIC_CHECK);
        }

        $permissions->makeHidden(['updated_at', 'created_at', 'guard_name']);

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions found',
            'data' => $permissions
        ], 200, [], JSON_NUMERIC_CHECK);
    }
    public function show(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'permission_id' => 'required|exists:permissions,id',
        ]);

        if ($validatedData->fails()) {
            $errors = implode(' ', $validatedData->errors()->all());
            return response()->json([
                'status' => 'failed',
                'message' => $errors
            ], 422, [], JSON_NUMERIC_CHECK);
        }

        $permission = Permission::find($request->permission_id);

        if (!$permission) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Permission not found'
            ], 404, [], JSON_NUMERIC_CHECK);
        }

        $permission->makeHidden(['updated_at', 'created_at', 'guard_name']);

        return response()->json([
            'status' => 'success',
            'message' => "Permission found",
            'data' => $permission
        ], 200, [], JSON_NUMERIC_CHECK);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:permissions,name',

        ]);

        if ($validator->fails()) {
            $errors = implode(' ', $validator->errors()->all());
            return response()->json([
                'status' => "failed",
                'message' => $errors
            ], 422);
        }
        $permission = Permission::create([
            'name' => $request->name,
        ]);

        $permission->makeHidden(['updated_at', 'created_at', 'guard_name']);

        return response()->json([
            'status' => 'success',
            'message' => "Permission created successfully",
            'data' => $permission,
        ], 200, [], JSON_NUMERIC_CHECK);
    }
    public function destroy(Request $request)
    {
        // Validate the incoming request
        $validatedData = Validator::make($request->all(), [
            'permission_id' => 'required|exists:permissions,id',
        ]);

        if ($validatedData->fails()) {
            $errors = implode(' ', $validatedData->errors()->all());
            return response()->json([
                'status' => 'failed',
                'message' => $errors
            ], 422, [], JSON_NUMERIC_CHECK);
        }

        // Fetch the permission
        $permission = Permission::find($request->permission_id);

        if (!$permission) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Permission not found'
            ], 404, [], JSON_NUMERIC_CHECK);
        }

        try {
            // If Permission-Role relationships exist, detach them
            $permission->roles()->detach();
            // Delete the permission instance
            $permission->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Permission deleted successfully'
            ], 200, [], JSON_NUMERIC_CHECK);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ], 500, [], JSON_NUMERIC_CHECK);
        }
    }
}
