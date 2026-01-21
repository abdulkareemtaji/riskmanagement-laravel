<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MitigationAction;
use App\Models\Risk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MitigationActionController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('permission:view-mitigation-actions', ['only' => ['index', 'show', 'getByRisk']]);
        $this->middleware('permission:create-mitigation-actions', ['only' => ['store']]);
        $this->middleware('permission:edit-mitigation-actions', ['only' => ['update']]);
        $this->middleware('permission:delete-mitigation-actions', ['only' => ['destroy']]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/mitigation-actions",
     *     summary="Get list of mitigation actions",
     *     tags={"Mitigation Actions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"planned", "in_progress", "completed", "cancelled"})
     *     ),
     *     @OA\Parameter(
     *         name="assigned_to",
     *         in="query",
     *         description="Filter by assigned user ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="overdue",
     *         in="query",
     *         description="Filter overdue actions",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mitigation actions retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = MitigationAction::with(['risk', 'assignedUser']);

        // Apply role-based filtering
        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks')) {
            $query->whereHas('risk', function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            })->orWhere('assigned_to', $user->id);
        }

        // Apply filters
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('assigned_to')) {
            $query->byAssignedUser($request->assigned_to);
        }

        if ($request->boolean('overdue')) {
            $query->overdue();
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $actions = $query->paginate($perPage);

        return response()->json([
            'message' => 'Mitigation actions retrieved successfully',
            'data' => $actions,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/mitigation-actions",
     *     summary="Create a new mitigation action",
     *     tags={"Mitigation Actions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"risk_id","title","description","assigned_to","due_date"},
     *             @OA\Property(property="risk_id", type="integer"),
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="assigned_to", type="integer"),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="priority", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="cost_estimate", type="number"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Mitigation action created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'risk_id' => 'required|exists:risks,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'assigned_to' => 'required|exists:users,id',
            'due_date' => 'required|date|after:today',
            'priority' => 'nullable|integer|min:1|max:5',
            'cost_estimate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if user can create actions for this risk
        $risk = Risk::findOrFail($request->risk_id);
        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks') && $risk->owner_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to create actions for this risk'
            ], 403);
        }

        $action = MitigationAction::create($validator->validated());
        $action->load(['risk', 'assignedUser']);

        return response()->json([
            'message' => 'Mitigation action created successfully',
            'data' => $action,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/mitigation-actions/{id}",
     *     summary="Get a specific mitigation action",
     *     tags={"Mitigation Actions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mitigation action retrieved successfully"
     *     )
     * )
     */
    public function show($id)
    {
        $action = MitigationAction::with(['risk', 'assignedUser'])->findOrFail($id);

        // Check if user can view this action
        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks') && 
            $action->risk->owner_id !== $user->id && 
            $action->assigned_to !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to view this mitigation action'
            ], 403);
        }

        return response()->json([
            'message' => 'Mitigation action retrieved successfully',
            'data' => $action,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/mitigation-actions/{id}",
     *     summary="Update a mitigation action",
     *     tags={"Mitigation Actions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="assigned_to", type="integer"),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="priority", type="integer"),
     *             @OA\Property(property="cost_estimate", type="number"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mitigation action updated successfully"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $action = MitigationAction::findOrFail($id);

        // Check if user can edit this action
        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks') && 
            $action->risk->owner_id !== $user->id && 
            $action->assigned_to !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to edit this mitigation action'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:planned,in_progress,completed,cancelled',
            'assigned_to' => 'sometimes|required|exists:users,id',
            'due_date' => 'sometimes|required|date',
            'priority' => 'nullable|integer|min:1|max:5',
            'cost_estimate' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        
        // Only allow assignment change if user has permission
        if (isset($data['assigned_to']) && !$user->hasPermissionTo('assign-mitigation-actions')) {
            unset($data['assigned_to']);
        }

        $action->update($data);
        $action->load(['risk', 'assignedUser']);

        return response()->json([
            'message' => 'Mitigation action updated successfully',
            'data' => $action,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/mitigation-actions/{id}",
     *     summary="Delete a mitigation action",
     *     tags={"Mitigation Actions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mitigation action deleted successfully"
     *     )
     * )
     */
    public function destroy($id)
    {
        $action = MitigationAction::findOrFail($id);

        // Check if user can delete this action
        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks') && $action->risk->owner_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to delete this mitigation action'
            ], 403);
        }

        $action->delete();

        return response()->json([
            'message' => 'Mitigation action deleted successfully',
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/risks/{risk}/mitigation-actions",
     *     summary="Get mitigation actions for a specific risk",
     *     tags={"Mitigation Actions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="risk",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mitigation actions for risk retrieved successfully"
     *     )
     * )
     */
    public function getByRisk($riskId)
    {
        $risk = Risk::findOrFail($riskId);

        // Check if user can view this risk's actions
        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks') && $risk->owner_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to view actions for this risk'
            ], 403);
        }

        $actions = MitigationAction::with(['assignedUser'])
                                 ->where('risk_id', $riskId)
                                 ->orderBy('priority')
                                 ->orderBy('due_date')
                                 ->get();

        return response()->json([
            'message' => 'Mitigation actions retrieved successfully',
            'data' => $actions,
        ]);
    }
}
