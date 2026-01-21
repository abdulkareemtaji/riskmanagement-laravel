<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RiskResource;
use App\Models\Risk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class RiskController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Permission checks are handled within each method
        // to allow for more granular control and proper relationship loading
    }

    /**
     * @OA\Get(
     *     path="/api/v1/risks",
     *     summary="Get list of risks",
     *     tags={"Risks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category",
     *         @OA\Schema(type="string", enum={"operational", "financial", "compliance", "strategic", "reputational"})
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"identified", "assessed", "mitigating", "closed"})
     *     ),
     *     @OA\Parameter(
     *         name="risk_level",
     *         in="query",
     *         description="Filter by risk level",
     *         @OA\Schema(type="string", enum={"low", "medium", "high"})
     *     ),
     *     @OA\Parameter(
     *         name="owner_id",
     *         in="query",
     *         description="Filter by owner ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of risks retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Risk::with(['owner', 'mitigationActions', 'latestAssessment']);

        // Apply role-based filtering
        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks')) {
            $query->where('owner_id', $user->id);
        }

        // Apply filters
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('risk_level')) {
            $query->byRiskLevel($request->risk_level);
        }

        if ($request->has('owner_id') && $user->hasPermissionTo('manage-all-risks')) {
            $query->byOwner($request->owner_id);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $risks = $query->paginate($perPage);

        return RiskResource::collection($risks)->additional([
            'message' => 'Risks retrieved successfully',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/risks",
     *     summary="Create a new risk",
     *     tags={"Risks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","description","category","likelihood","impact","identified_date"},
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="category", type="string", enum={"operational", "financial", "compliance", "strategic", "reputational"}),
     *             @OA\Property(property="likelihood", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="impact", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="owner_id", type="integer"),
     *             @OA\Property(property="department", type="string"),
     *             @OA\Property(property="identified_date", type="string", format="date"),
     *             @OA\Property(property="target_closure_date", type="string", format="date"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Risk created successfully"
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|in:operational,financial,compliance,strategic,reputational',
            'likelihood' => 'required|integer|min:1|max:5',
            'impact' => 'required|integer|min:1|max:5',
            'owner_id' => 'nullable|exists:users,id',
            'department' => 'nullable|string|max:255',
            'identified_date' => 'required|date',
            'target_closure_date' => 'nullable|date|after:identified_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Set owner to current user if not specified or user doesn't have permission
        if (!isset($data['owner_id']) || !auth()->user()->hasPermissionTo('manage-all-risks')) {
            $data['owner_id'] = auth()->id();
        }

        $risk = Risk::create($data);
        $risk->load(['owner', 'mitigationActions']);

        // Send email notification if high risk
        if ($risk->isHighRisk()) {
            $this->sendHighRiskNotification($risk);
        }

        return (new RiskResource($risk))->additional([
            'message' => 'Risk created successfully',
        ])->response()->setStatusCode(201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/risks/{id}",
     *     summary="Get a specific risk",
     *     tags={"Risks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Risk retrieved successfully"
     *     ),
     *     @OA\Response(response=404, description="Risk not found")
     * )
     */
    public function show($id)
    {
        $risk = Risk::with(['owner', 'mitigationActions.assignedUser', 'assessments.assessor'])
                   ->findOrFail($id);

        // Check if user can view this risk
        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks') && $risk->owner_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to view this risk'
            ], 403);
        }

        return (new RiskResource($risk))->additional([
            'message' => 'Risk retrieved successfully',
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/risks/{id}",
     *     summary="Update a risk",
     *     tags={"Risks"},
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
     *             @OA\Property(property="category", type="string"),
     *             @OA\Property(property="likelihood", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="impact", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="status", type="string"),
     *             @OA\Property(property="owner_id", type="integer"),
     *             @OA\Property(property="department", type="string"),
     *             @OA\Property(property="target_closure_date", type="string", format="date"),
     *             @OA\Property(property="actual_closure_date", type="string", format="date"),
     *             @OA\Property(property="notes", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Risk updated successfully"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $risk = Risk::findOrFail($id);

        // Check if user can edit this risk
        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks') && $risk->owner_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to edit this risk'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category' => 'sometimes|required|in:operational,financial,compliance,strategic,reputational',
            'likelihood' => 'sometimes|required|integer|min:1|max:5',
            'impact' => 'sometimes|required|integer|min:1|max:5',
            'status' => 'sometimes|required|in:identified,assessed,mitigating,closed',
            'owner_id' => 'sometimes|exists:users,id',
            'department' => 'nullable|string|max:255',
            'target_closure_date' => 'nullable|date',
            'actual_closure_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Only allow owner change if user has permission
        if (isset($data['owner_id']) && !$user->hasPermissionTo('manage-all-risks')) {
            unset($data['owner_id']);
        }

        $wasHighRisk = $risk->isHighRisk();
        $risk->update($data);
        $risk->load(['owner', 'mitigationActions']);

        // Send notification if risk became high risk
        if (!$wasHighRisk && $risk->isHighRisk()) {
            $this->sendHighRiskNotification($risk);
        }

        return (new RiskResource($risk))->additional([
            'message' => 'Risk updated successfully',
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/risks/{id}",
     *     summary="Delete a risk",
     *     tags={"Risks"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Risk deleted successfully"
     *     )
     * )
     */
    public function destroy($id)
    {
        $risk = Risk::findOrFail($id);

        // Check if user can delete this risk
        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks') && $risk->owner_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to delete this risk'
            ], 403);
        }

        $risk->delete();

        return response()->json([
            'message' => 'Risk deleted successfully',
        ]);
    }

    /**
     * Send high risk notification email.
     */
    private function sendHighRiskNotification(Risk $risk)
    {
        try {
            // In a real application, you would implement proper email notification
            // For now, we'll just log it
            \Log::info("High risk notification: Risk '{$risk->title}' (ID: {$risk->id}) has a high risk score of {$risk->risk_score}");
        } catch (\Exception $e) {
            \Log::error("Failed to send high risk notification: " . $e->getMessage());
        }
    }
}
