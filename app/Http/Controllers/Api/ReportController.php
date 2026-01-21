<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Risk;
use App\Models\MitigationAction;
use App\Models\RiskAssessment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
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
     *     path="/api/v1/reports/dashboard",
     *     summary="Get dashboard summary data",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data retrieved successfully"
     *     )
     * )
     */
    public function dashboard()
    {
        $user = auth()->user();
        
        // Build base query with role-based filtering
        $riskQuery = Risk::query();
        $actionQuery = MitigationAction::query();
        
        if (!$user->hasPermissionTo('manage-all-risks')) {
            $riskQuery->where('owner_id', $user->id);
            $actionQuery->whereHas('risk', function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            });
        }

        $data = [
            'total_risks' => $riskQuery->count(),
            'high_risks' => $riskQuery->where('risk_score', '>=', 15)->count(),
            'medium_risks' => $riskQuery->whereBetween('risk_score', [8, 14])->count(),
            'low_risks' => $riskQuery->where('risk_score', '<', 8)->count(),
            'open_risks' => $riskQuery->whereIn('status', ['identified', 'assessed', 'mitigating'])->count(),
            'closed_risks' => $riskQuery->where('status', 'closed')->count(),
            'total_actions' => $actionQuery->count(),
            'overdue_actions' => $actionQuery->overdue()->count(),
            'completed_actions' => $actionQuery->where('status', 'completed')->count(),
            'in_progress_actions' => $actionQuery->where('status', 'in_progress')->count(),
        ];

        return response()->json([
            'message' => 'Dashboard data retrieved successfully',
            'data' => $data,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/risk-summary",
     *     summary="Get risk summary statistics",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Risk summary retrieved successfully"
     *     )
     * )
     */
    public function riskSummary()
    {
        $user = auth()->user();
        $query = Risk::query();
        
        if (!$user->hasPermissionTo('manage-all-risks')) {
            $query->where('owner_id', $user->id);
        }

        $summary = [
            'by_status' => $query->select('status', DB::raw('count(*) as count'))
                                ->groupBy('status')
                                ->pluck('count', 'status'),
            'by_category' => $query->select('category', DB::raw('count(*) as count'))
                                  ->groupBy('category')
                                  ->pluck('count', 'category'),
            'by_risk_level' => [
                'high' => $query->where('risk_score', '>=', 15)->count(),
                'medium' => $query->whereBetween('risk_score', [8, 14])->count(),
                'low' => $query->where('risk_score', '<', 8)->count(),
            ],
            'average_risk_score' => round($query->avg('risk_score'), 2),
            'total_risks' => $query->count(),
        ];

        return response()->json([
            'message' => 'Risk summary retrieved successfully',
            'data' => $summary,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/risk-matrix",
     *     summary="Get risk matrix data for visualization",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Risk matrix data retrieved successfully"
     *     )
     * )
     */
    public function riskMatrix()
    {
        $user = auth()->user();
        $query = Risk::query();
        
        if (!$user->hasPermissionTo('manage-all-risks')) {
            $query->where('owner_id', $user->id);
        }

        $risks = $query->select('id', 'title', 'likelihood', 'impact', 'risk_score', 'category')
                      ->get()
                      ->groupBy(function ($risk) {
                          return $risk->likelihood . '-' . $risk->impact;
                      });

        $matrix = [];
        for ($likelihood = 1; $likelihood <= 5; $likelihood++) {
            for ($impact = 1; $impact <= 5; $impact++) {
                $key = $likelihood . '-' . $impact;
                $matrix[$likelihood][$impact] = [
                    'count' => isset($risks[$key]) ? $risks[$key]->count() : 0,
                    'risks' => isset($risks[$key]) ? $risks[$key]->values() : [],
                    'risk_score' => $likelihood * $impact,
                ];
            }
        }

        return response()->json([
            'message' => 'Risk matrix data retrieved successfully',
            'data' => $matrix,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/risks-by-category",
     *     summary="Get risks breakdown by category",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Risks by category retrieved successfully"
     *     )
     * )
     */
    public function risksByCategory()
    {
        $user = auth()->user();
        $query = Risk::query();
        
        if (!$user->hasPermissionTo('manage-all-risks')) {
            $query->where('owner_id', $user->id);
        }

        $data = $query->select(
                    'category',
                    DB::raw('count(*) as total_count'),
                    DB::raw('sum(case when risk_score >= 15 then 1 else 0 end) as high_count'),
                    DB::raw('sum(case when risk_score between 8 and 14 then 1 else 0 end) as medium_count'),
                    DB::raw('sum(case when risk_score < 8 then 1 else 0 end) as low_count'),
                    DB::raw('avg(risk_score) as avg_risk_score')
                )
                ->groupBy('category')
                ->get()
                ->map(function ($item) {
                    $item->avg_risk_score = round($item->avg_risk_score, 2);
                    return $item;
                });

        return response()->json([
            'message' => 'Risks by category retrieved successfully',
            'data' => $data,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/risks-by-department",
     *     summary="Get risks breakdown by department",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Risks by department retrieved successfully"
     *     )
     * )
     */
    public function risksByDepartment()
    {
        $user = auth()->user();
        $query = Risk::query();
        
        if (!$user->hasPermissionTo('manage-all-risks')) {
            $query->where('owner_id', $user->id);
        }

        $data = $query->select(
                    'department',
                    DB::raw('count(*) as total_count'),
                    DB::raw('sum(case when risk_score >= 15 then 1 else 0 end) as high_count'),
                    DB::raw('sum(case when risk_score between 8 and 14 then 1 else 0 end) as medium_count'),
                    DB::raw('sum(case when risk_score < 8 then 1 else 0 end) as low_count'),
                    DB::raw('avg(risk_score) as avg_risk_score')
                )
                ->whereNotNull('department')
                ->groupBy('department')
                ->get()
                ->map(function ($item) {
                    $item->avg_risk_score = round($item->avg_risk_score, 2);
                    return $item;
                });

        return response()->json([
            'message' => 'Risks by department retrieved successfully',
            'data' => $data,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/overdue-actions",
     *     summary="Get overdue mitigation actions",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Overdue actions retrieved successfully"
     *     )
     * )
     */
    public function overdueActions()
    {
        $user = auth()->user();
        $query = MitigationAction::with(['risk', 'assignedUser']);
        
        if (!$user->hasPermissionTo('manage-all-risks')) {
            $query->whereHas('risk', function ($q) use ($user) {
                $q->where('owner_id', $user->id);
            });
        }

        $actions = $query->overdue()
                        ->orderBy('due_date')
                        ->get()
                        ->map(function ($action) {
                            $action->days_overdue = now()->diffInDays($action->due_date);
                            return $action;
                        });

        return response()->json([
            'message' => 'Overdue actions retrieved successfully',
            'data' => $actions,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/reports/high-risk-items",
     *     summary="Get high risk items requiring attention",
     *     tags={"Reports"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="High risk items retrieved successfully"
     *     )
     * )
     */
    public function highRiskItems()
    {
        $user = auth()->user();
        $query = Risk::with(['owner', 'mitigationActions']);
        
        if (!$user->hasPermissionTo('manage-all-risks')) {
            $query->where('owner_id', $user->id);
        }

        $highRisks = $query->where('risk_score', '>=', 15)
                          ->whereIn('status', ['identified', 'assessed', 'mitigating'])
                          ->orderBy('risk_score', 'desc')
                          ->get();

        return response()->json([
            'message' => 'High risk items retrieved successfully',
            'data' => $highRisks,
        ]);
    }
}
