<?php

namespace App\Http\Controllers;

use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    /**
     * List all projects. See ProjectPolicy::viewAny.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        return response()->json([
            'projects' => ProjectResource::collection(Project::all()),
        ]);
    }

    /**
     * Create a project. See ProjectPolicy::create. created_by is taken from
     * the authenticated user, never from client input.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = Project::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Project created successfully.',
            'project' => new ProjectResource($project),
        ], 201);
    }

    /**
     * Show a single project. See ProjectPolicy::view.
     */
    public function show(Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json([
            'project' => new ProjectResource($project),
        ]);
    }

    /**
     * Update a project. See ProjectPolicy::update.
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return response()->json([
            'message' => 'Project updated successfully.',
            'project' => new ProjectResource($project),
        ]);
    }

    /**
     * Delete a project. Only Admins are authorized (see ProjectPolicy::delete).
     */
    public function destroy(Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }
}