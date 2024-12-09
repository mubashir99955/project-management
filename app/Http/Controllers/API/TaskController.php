<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

 
class TaskController extends Controller implements HasMiddleware
{
    // Middleware for Role and Permission Control
    public static function middleware(): array
    {
        return [
            new Middleware('permission:create tasks', only: ['store']),
            new Middleware('permission:view tasks', only: ['index']),
            new Middleware('permission:view single tasks', only: ['show']),
            new Middleware('permission:update tasks', only: ['update']),
            new Middleware('permission:delete tasks', only: ['destroy']),
        ];
    }
    // View All Tasks of a Project or User
    public function index(Request $request)
    {
        $user = Auth::user();
    
            $tasks = Task::with(['project', 'user'])->get();
       
        if ($tasks->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Tasks not found',
                'data' => $tasks // Empty collection
            ], 200, [], JSON_NUMERIC_CHECK);
        }
    
        return response()->json([
            'status' => 'success',
            'message' => 'Tasks found',
            'data' => $tasks
        ], 200, [], JSON_NUMERIC_CHECK);
    }
    // View Single Task
    public function show(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id',
        ]);

        if ($validatedData->fails()) {
            $errors = implode(' ', $validatedData->errors()->all());
            return response()->json([
                'status' => 'failed',
                'message' => $errors
            ], 422, [], JSON_NUMERIC_CHECK);
        }
        $user = Auth::user();
        if ($user->hasRole('User')) {
            $task = Task::with(['project', 'user'])
                ->where('id', $request->task_id)
                ->where('user_id', $user->id)
                ->first();
        } else {
            $task = Task::with(['project', 'user'])->find($request->task_id);
        }
        if (!$task) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Task not found'
            ], 404, [], JSON_NUMERIC_CHECK);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Task found",
            'data' => $task
        ], 200, [], JSON_NUMERIC_CHECK);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|date',
            'status' => 'required|in:pending,completed',
            'project_id' => 'required|exists:projects,id',
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            $errors = implode(' ', $validator->errors()->all());
            return response()->json([
                'status' => "failed",
                'message' => $errors
            ], 422);
        }
        // Check if user is assigned to the project
        $project = Project::find($request->project_id);
        // Check if the user is part of the project
        if (!$project->users->contains($request->user_id)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'User cannot assigned to this task Because this user did not belong to this project',
            ], 400);
        }
        // Create the task
        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'status' => $request->status,
            'project_id' => $request->project_id,
            'user_id' => $request->user_id,
        ]);


        return response()->json([
            'status' => 'success',
            'message' => 'Task created successfully.',
            'data' => $task
        ], 200);
    }
    // Update Task Details
    public function update(Request $request)
    {
        $user = Auth::user();
        if ($user->hasRole('User')) {
            $validator = Validator::make($request->all(), [
                'task_id' => 'required|exists:tasks,id',
                'status' => 'required|in:pending,completed',
            ]);

            if ($validator->fails()) {
                $errors = implode(' ', $validator->errors()->all());
                return response()->json([
                    'status' => 'failed',
                    'message' => $errors
                ], 422, [], JSON_NUMERIC_CHECK);
            }

            $task = Task::find($request->task_id);

            if (!$task) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Bad Request'
                ], 400, [], JSON_NUMERIC_CHECK);
            }
            $user = Auth:: user();
            if ($user->hasRole('User')) {
            $task->update([
                'status' => $request->status,
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Status Updated successfully.',
                'data' => $task
            ], 200);
            }else{
                return response()->json([
                    'status' => 'failed',
                    'message' => 'bad request.',
                ], 200);
            }
        }else{
            $validator = Validator::make($request->all(), [
                'task_id' => 'required|exists:tasks,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'due_date' => 'required|date',
                'status' => 'required|in:pending,completed',
                'project_id' => 'required|exists:projects,id',
                'user_id' => 'required|exists:users,id',
            ]);
    
            if ($validator->fails()) {
                $errors = implode(' ', $validator->errors()->all());
                return response()->json([
                    'status' => 'failed',
                    'message' => $errors
                ], 422, [], JSON_NUMERIC_CHECK);
            }
    
            $task = Task::find($request->task_id);
    
            if (!$task) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Bad Request'
                ], 400, [], JSON_NUMERIC_CHECK);
            }
            $user = Auth:: user();
    
            // Check if the user is assigned to the project
            $project = Project::find($request->project_id);
            if (!$project->users->contains($request->user_id)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'User cannot assigned to this task',
                ], 400);
            }
            // Update the task with the provided data
            $task->update([
                'title' => $request->title,
                'description' => $request->description ?? $task->description,
                'due_date' => $request->due_date,
                'status' => $request->status,
                'project_id' => $request->project_id,
                'user_id' => $request->user_id,
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Task Updated successfully.',
                'data' => $task
            ], 200);
        }

    }
    // Delete Task
    public function destroy(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'task_id' => 'required|exists:tasks,id',
        ]);

        // If validation fails, return the error messages
        if ($validator->fails()) {
            $errors = implode(' ', $validator->errors()->all());
            return response()->json([
                'status' => 'failed',
                'message' => $errors,
            ], 422, [], JSON_NUMERIC_CHECK);
        }

        // Find the task
        $task = Task::find($request->task_id);

        // If the task is not found, return a 400 Bad Request response
        if (!$task) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Bad Request. Task not found.',
            ], 400, [], JSON_NUMERIC_CHECK);
        }

        // Delete the task
        $task->delete();

        // Return success response
        return response()->json([
            'status' => 'success',
            'message' => 'Task deleted successfully.',
            'data' => $task, // Optional: Include deleted task details
        ], 200, [], JSON_NUMERIC_CHECK);
    }
}
