<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use App\Http\Controllers\MediaController;

class ProjectController extends Controller implements HasMiddleware
{
    // Middleware for Role and Permission Control
    public static function middleware(): array
    {
        return [
            new Middleware('permission:create project', only: ['store']),
            new Middleware('permission:view project', only: ['index']),
            new Middleware('permission:view single project', only: ['show']),
            new Middleware('permission:update project', only: ['update']),
            new Middleware('permission:delete project', only: ['destroy']),
        ];
    }
    protected $mediaController;
    public function __construct(MediaController $mediaController)
    {
        $this->mediaController = $mediaController;
    }
    public function index()
    {
        $user = Auth::user();
    
        $projects = Project::with(['tasks', 'users','media'])->get();
        if ($projects->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Projects not found',
                'data' => $projects 
            ], 200, [], JSON_NUMERIC_CHECK);
        }
    
        return response()->json([
            'status' => 'success',
            'message' => 'Projects found',
            'data' => $projects
        ], 200, [], JSON_NUMERIC_CHECK);
    }
    public function show(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
        ]);

        if ($validatedData->fails()) {
            $errors = implode(' ', $validatedData->errors()->all());
            return response()->json([
                'status' => 'failed',
                'message' => $errors
            ], 422, [], JSON_NUMERIC_CHECK);
        }

        $project = Project::with(['tasks', 'users'])->find($request->project_id);

        if (!$project) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Project not found'
            ], 404, [], JSON_NUMERIC_CHECK);
        }

        return response()->json([
            'status' => 'success',
            'message' => "Project found",
            'data' => $project
        ], 200, [], JSON_NUMERIC_CHECK);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            $errors = implode(' ', $validator->errors()->all());
            return response()->json([
                'status' => "failed",
                'message' => $errors
            ], 422);
        }

        $project = Project::create([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date
        ]);
        // Assign users to the project
        if ($request->has('user_ids')) {
            $project->users()->sync($request->user_ids);
        }
        if ($request->hasFile('project_document')) {
            $fileName = "project_".$project->id;
            $this->mediaController->uploadMedia($request, $project, $request->file('project_document'), 'project_documents', $fileName, false);
        }
        $project->load(['tasks', 'users','media']);
        return response()->json([
            'status' => 'success',
            'message' => "Project created successfully",
            'data' => $project,
        ], 200, [], JSON_NUMERIC_CHECK);
    }
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            $errors = implode(' ', $validator->errors()->all());
            return response()->json([
                'status' => "failed",
                'message' => $errors
            ], 422);
        }

        $project = Project::find($request->project_id);

        if (!$project) {
            return response()->json([
                'status' => "failed",
                'message' => "Project not found"
            ], 404);
        }

        // Update the project details
        $project->update([
            'name' => $request->name,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
        ]);

        // Update user assignments for the project
        if ($request->has('user_ids')) {
            $project->users()->sync($request->user_ids);
        }

        if ($request->hasFile('project_document')) {
            // Check if the user has existing media and unlink it
            if ($project->media) {
                // Assuming you have a method to handle media deletion
                $this->mediaController->deleteMedia($project->media->media_id);
            }
            $fileName = $project->id;
            $this->mediaController->uploadMedia($request, $project, $request->file('project_document'), 'project_documents', $fileName, true);
        }
        $project->load(['tasks', 'users','media']);

        return response()->json([
            'status' => 'success',
            'message' => "Project updated successfully",
            'data' => $project,
        ], 200, [], JSON_NUMERIC_CHECK);
    }
    public function destroy(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
        ]);

        if ($validatedData->fails()) {
            $errors = implode(' ', $validatedData->errors()->all());
            return response()->json([
                'status' => 'failed',
                'message' => $errors
            ], 422, [], JSON_NUMERIC_CHECK);
        }

        $project = Project::find($request->project_id);

        if (!$project) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Project not found'
            ], 404, [], JSON_NUMERIC_CHECK);
        }

        try {
            $project->tasks()->delete(); // Delete associated tasks
            $project->users()->detach(); // Detach users
            $project->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Project deleted successfully'
            ], 200, [], JSON_NUMERIC_CHECK);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ], 500, [], JSON_NUMERIC_CHECK);
        }
    }
}
 