<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Risk;
use App\Models\RiskAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RiskAssessmentController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('permission:view-risk-assessments', ['only' => ['index', 'show']]);
        $this->middleware('permission:create-risk-assessments', ['only' => ['store']]);
        $this->middleware('permission:edit-risk-assessments', ['only' => ['update']]);
        $this->middleware('permission:delete-risk-assessments', ['only' => ['destroy']]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/risks/{risk}/assessments",
     *     summary="Get assessments for a specific risk",
     *     tags={"Risk Assessments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="risk",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Risk assessments retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Check if this is for a specific risk
        if ($request->route('risk')) {
            return $this->getAssessmentsForRisk($request->route('risk'));
        }

        // Get all assessments with role-based filtering
        $query = RiskAssessment::with(['risk', 'assessor']);

        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks')) {
            $query->whereHas('risk', function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            });
        }

        // Apply filters
        if ($request->has('assessor_id')) {
            $query->byAssessor($request->assessor_id);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange($request->start_date, $request->end_date);
        }

        $perPage = $request->get('per_page', 15);
        $assessments = $query->orderBy('assessment_date', 'desc')->paginate($perPage);

        return response()->json([
            'message' => 'Risk assessments retrieved successfully',
            'data' => $assessments,
        ]);
    }

    /**
     * Get assessments for a specific risk.
     */
    private function getAssessmentsForRisk($riskId)
    {
        $risk = Risk::findOrFail($riskId);

        // Check if user can view this risk's assessments
        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks') && $risk->owner_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to view assessments for this risk'
            ], 403);
        }

        $assessments = RiskAssessment::with(['assessor'])
                                   ->where('risk_id', $riskId)
                                   ->orderBy('assessment_date', 'desc')
                                   ->get();

        return response()->json([
            'message' => 'Risk assessments retrieved successfully',
            'data' => $assessments,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/risks/{risk}/assessments",
     *     summary="Create a new risk assessment",
     *     tags={"Risk Assessments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="risk",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"likelihood_after","impact_after","assessment_date"},
     *             @OA\Property(property="likelihood_before", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="impact_before", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="likelihood_after", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="impact_after", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="assessment_notes", type="string"),
     *             @OA\Property(property="assessment_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Risk assessment created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $riskId = $request->route('risk');
        $risk = Risk::findOrFail($riskId);

        // Check if user can create assessments for this risk
        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks') && $risk->owner_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to create assessments for this risk'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'likelihood_before' => 'nullable|integer|min:1|max:5',
            'impact_before' => 'nullable|integer|min:1|max:5',
            'likelihood_after' => 'required|integer|min:1|max:5',
            'impact_after' => 'required|integer|min:1|max:5',
            'assessment_notes' => 'nullable|string',
            'assessment_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['risk_id'] = $riskId;
        $data['assessor_id'] = $user->id;

        $assessment = RiskAssessment::create($data);

        // Update the risk with new likelihood and impact
        $risk->update([
            'likelihood' => $data['likelihood_after'],
            'impact' => $data['impact_after'],
        ]);

        $assessment->load(['risk', 'assessor']);

        return response()->json([
            'message' => 'Risk assessment created successfully',
            'data' => $assessment,
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/risk-assessments/{id}",
     *     summary="Get a specific risk assessment",
     *     tags={"Risk Assessments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Risk assessment retrieved successfully"
     *     )
     * )
     */
    public function show($id)
    {
        $assessment = RiskAssessment::with(['risk', 'assessor'])->findOrFail($id);

        // Check if user can view this assessment
        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks') && $assessment->risk->owner_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to view this risk assessment'
            ], 403);
        }

        return response()->json([
            'message' => 'Risk assessment retrieved successfully',
            'data' => $assessment,
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/risk-assessments/{id}",
     *     summary="Update a risk assessment",
     *     tags={"Risk Assessments"},
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
     *             @OA\Property(property="likelihood_before", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="impact_before", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="likelihood_after", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="impact_after", type="integer", minimum=1, maximum=5),
     *             @OA\Property(property="assessment_notes", type="string"),
     *             @OA\Property(property="assessment_date", type="string", format="date")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Risk assessment updated successfully"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $assessment = RiskAssessment::findOrFail($id);

        // Check if user can edit this assessment
        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks') && 
            $assessment->risk->owner_id !== $user->id && 
            $assessment->assessor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to edit this risk assessment'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'likelihood_before' => 'nullable|integer|min:1|max:5',
            'impact_before' => 'nullable|integer|min:1|max:5',
            'likelihood_after' => 'sometimes|required|integer|min:1|max:5',
            'impact_after' => 'sometimes|required|integer|min:1|max:5',
            'assessment_notes' => 'nullable|string',
            'assessment_date' => 'sometimes|required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $assessment->update($validator->validated());

        // Update the risk if likelihood_after or impact_after changed
        if ($request->has('likelihood_after') || $request->has('impact_after')) {
            $assessment->risk->update([
                'likelihood' => $assessment->likelihood_after,
                'impact' => $assessment->impact_after,
            ]);
        }

        $assessment->load(['risk', 'assessor']);

        return response()->json([
            'message' => 'Risk assessment updated successfully',
            'data' => $assessment,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/risk-assessments/{id}",
     *     summary="Delete a risk assessment",
     *     tags={"Risk Assessments"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Risk assessment deleted successfully"
     *     )
     * )
     */
    public function destroy($id)
    {
        $assessment = RiskAssessment::findOrFail($id);

        // Check if user can delete this assessment
        $user = auth()->user();
        if (!$user->hasPermissionTo('manage-all-risks') && 
            $assessment->risk->owner_id !== $user->id && 
            $assessment->assessor_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized to delete this risk assessment'
            ], 403);
        }

        $assessment->delete();

        return response()->json([
            'message' => 'Risk assessment deleted successfully',
        ]);
    }
}
