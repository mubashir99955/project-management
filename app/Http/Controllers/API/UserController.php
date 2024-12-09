<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\MediaController;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;


class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:create user', only: ['store']),
            new Middleware('permission:view user', only: ['index']),
            new Middleware('permission:view single user', only: ['show']),
            new Middleware('permission:update user', only: ['update']),
            new Middleware('permission:delete user', only: ['destroy']),
        ];
    }
    protected $mediaController;
    public function __construct(MediaController $mediaController)
    {
        $this->mediaController = $mediaController;
    }
    public function index()
    {
        // $users = User::with('roles.permissions')->get();
        $users = User::with('roles','media')->get();


        return response()->json([
            'status' => 'success',
            'data' => $users
        ], 200, [], JSON_NUMERIC_CHECK);
    }
    // CREATE a new user
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'password' => 'string|min:8',
            'country' => 'required|string|max:50',
            'role_name' => 'required|string|exists:roles,name'
        ]);

        if ($validator->fails()) {
            $errors = implode(' ', $validator->errors()->all());
            return response()->json([
                'status' => 'failed',
                'message' => $errors
            ], 422, [], JSON_NUMERIC_CHECK);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'country' => $request->country,
            'phone_number' => $request->phone_number,
        ]);
        // Assign role
        if ($user) {
            $role = Role::where('name', $request->role_name)->where('guard_name', 'sanctum')->first();
            // dd($role);
            if ($role) {
                $user->syncRoles([$role->name]);
            }
            if ($request->hasFile('profile_photo')) {
                $fileName = $user->id;
                $this->mediaController->uploadMedia($request, $user, $request->file('profile_photo'), 'user_uplaods', $fileName, true);
            }
        }
        // $user->load('media', 'role');
        return response()->json([
            'status' => 'success',
            'message' => "User created successfully",
            'data' => $user,
        ], 200, [], JSON_NUMERIC_CHECK);
    }

    // GET a single user
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);
        if ($validator->fails()) {
            $errors = implode(' ', $validator->errors()->all());
            return response()->json([
                'status' => 'failed',
                'message' => $errors
            ], 422, [], JSON_NUMERIC_CHECK);
        }
        $user = User::with('roles')->find($request->user_id);
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Bad Request'
            ], 400, [], JSON_NUMERIC_CHECK);
        }

        return response()->json([
            'status' => 'success',
            'message' => "User found",
            'data' => $user
        ], 200, [], JSON_NUMERIC_CHECK);
    }

    // UPDATE a user
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'first_name' => 'string|max:50',
            'last_name' => 'string|max:50',
            'email' => 'required|email|exists:users,email',
            'country' => 'required|string|max:50',
            'phone_number' => 'string|max:15',
            'role_name' => 'required|string|exists:roles,name',
            'account_status' => 'required|string|in:active,inactive,blocked,deleted',
        ]);

        if ($validator->fails()) {
            $errors = implode(' ', $validator->errors()->all());
            return response()->json([
                'status' => 'failed',
                'message' => $errors
            ], 422, [], JSON_NUMERIC_CHECK);
        }
        $user = User::with('media','roles')->find($request->user_id);
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Bad request'
            ], 400, [], JSON_NUMERIC_CHECK);
        }
        $fields = [
            'first_name',
            'last_name',
            // 'email',
            'password',
            'country',
            'profile_picture',
            'phone_number',
            'account_status',
            'user_role',
        ];

        // Filter out fields that exist in the request
        $data = [];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $data[$field] = $request->$field;
            }
        }
        if ($request->hasFile('profile_photo')) {
            // Check if the user has existing media and unlink it
            if ($user->media) {
                // Assuming you have a method to handle media deletion
                $this->mediaController->deleteMedia($user->media->media_id);
            }
            $fileName = $user->id;
            $this->mediaController->uploadMedia($request, $user, $request->file('profile_photo'), 'user_uplaods', $fileName, true);
        }

        $user->update($data);
        // Update role
        if ($request->role_name) {
            $role = Role::where('name', $request->role_name)->where('guard_name', 'sanctum')->first();
            if ($role) {
                $user->syncRoles([$role->name]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Changes saved successfully',
            'data' => $user
        ], 200, [], JSON_NUMERIC_CHECK);
    }
    // DELETE a user
    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);
        if ($validator->fails()) {
            $errors = implode(' ', $validator->errors()->all());
            return response()->json([
                'status' => 'failed',
                'message' => $errors
            ], 422, [], JSON_NUMERIC_CHECK);
        }

        $user = User::find($request->user_id);
        if (!$user) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Bad request'
            ], 400, [], JSON_NUMERIC_CHECK);
        }
        if ($user->media) {
            $this->mediaController->deleteMedia($user->media->media_id);
        }
        $user->roles()->detach();
        $user->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ], 200, [], JSON_NUMERIC_CHECK);
    }

    public function userRoles()
    {
        $users = User::with('roles')->get();
        $users->map(function (User $user) {
            $role = $user->roles->first();
            $user->role_id = $role ? $role->id : null;
            $user->role = $role;
            unset($user->roles);
        });
        return response()->json([
            'data' => $users
        ], 200, [], JSON_NUMERIC_CHECK);
    }
}
