<?php

namespace App\Http\Controllers;

use App\Models\SavingsGoal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SavingsGoalController extends Controller
{
    /**
     * Display a listing of the savings goals.
     */
    public function index(Request $request)
    {
        try {
            $query = SavingsGoal::where('user_id', $request->user()->id);

            // Filter by status
            if ($request->has('status')) {
                if ($request->status === 'active') {
                    $query->active();
                } elseif ($request->status === 'completed') {
                    $query->completed();
                }
            }

            // Filter by priority
            if ($request->has('priority') && $request->priority) {
                $query->priority($request->priority);
            }

            // Filter by category
            if ($request->has('category') && $request->category) {
                $query->category($request->category);
            }

            // Get monthly income for affordability check
            $monthlyIncome = $request->user()->getTotalIncomeForMonth(now()->month, now()->year);

            $goals = $query->orderBy('priority', 'desc')
                ->orderBy('target_date', 'asc')
                ->get()
                ->map(function ($goal) use ($monthlyIncome) {
                    return [
                        'id' => $goal->id,
                        'name' => $goal->name,
                        'target_amount' => $goal->target_amount,
                        'formatted_target_amount' => $goal->getFormattedTargetAmount(),
                        'target_date' => $goal->target_date,
                        'category' => $goal->category,
                        'category_label' => $goal->getCategoryLabel(),
                        'category_icon' => $goal->getCategoryIcon(),
                        'priority' => $goal->priority,
                        'priority_label' => $goal->getPriorityLabel(),
                        'priority_badge' => $goal->getPriorityBadgeClass(),
                        'description' => $goal->description,
                        'is_completed' => $goal->is_completed,
                        'status' => $goal->getStatusLabel(),
                        'status_badge' => $goal->getStatusBadgeClass(),
                        'is_affordable' => $goal->isAffordable($monthlyIncome),
                        'amount_needed' => $goal->getAmountNeeded($monthlyIncome),
                        'formatted_amount_needed' => $goal->user->formatCurrency($goal->getAmountNeeded($monthlyIncome)),
                        'monthly_income' => $monthlyIncome,
                        'formatted_monthly_income' => $goal->user->formatCurrency($monthlyIncome),
                        'created_at' => $goal->created_at,
                        'updated_at' => $goal->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $goals,
                'summary' => [
                    'total' => $goals->count(),
                    'active' => $goals->filter(fn($g) => $g['status'] === 'Active')->count(),
                    'completed' => $goals->filter(fn($g) => $g['is_completed'])->count(),
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching savings goals: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch savings goals',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created savings goal.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'target_amount' => ['required', 'numeric', 'min:0.01'],
                'target_date' => ['required', 'date', 'after:today'],
                'category' => ['required', 'string', Rule::in(['emergency', 'vacation', 'education', 'home', 'vehicle', 'retirement', 'other'])],
                'priority' => ['nullable', 'string', Rule::in(['low', 'medium', 'high'])],
                'description' => ['nullable', 'string', 'max:1000'],
            ], [
                'name.required' => 'Item name is required',
                'target_amount.required' => 'Price is required',
                'target_amount.min' => 'Price must be greater than 0',
                'target_date.required' => 'Target date is required',
                'target_date.after' => 'Target date must be in the future',
                'category.required' => 'Category is required',
                'category.in' => 'Invalid category selected',
                'priority.in' => 'Invalid priority selected',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $goal = SavingsGoal::create([
                'user_id' => $request->user()->id,
                'name' => $request->name,
                'target_amount' => $request->target_amount,
                'target_date' => $request->target_date,
                'category' => $request->category,
                'priority' => $request->priority ?? 'medium',
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Item added to wishlist successfully',
                'data' => $goal
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating savings goal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified savings goal.
     */
    public function show(Request $request, $id)
    {
        try {
            $goal = SavingsGoal::where('user_id', $request->user()->id)
                ->find($id);

            if (!$goal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Savings goal not found'
                ], 404);
            }

            // Get monthly income for affordability check
            $monthlyIncome = $request->user()->getTotalIncomeForMonth(now()->month, now()->year);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $goal->id,
                    'name' => $goal->name,
                    'target_amount' => $goal->target_amount,
                    'formatted_target_amount' => $goal->getFormattedTargetAmount(),
                    'target_date' => $goal->target_date,
                    'category' => $goal->category,
                    'category_label' => $goal->getCategoryLabel(),
                    'category_icon' => $goal->getCategoryIcon(),
                    'priority' => $goal->priority,
                    'priority_label' => $goal->getPriorityLabel(),
                    'description' => $goal->description,
                    'is_completed' => $goal->is_completed,
                    'status' => $goal->getStatusLabel(),
                    'is_affordable' => $goal->isAffordable($monthlyIncome),
                    'amount_needed' => $goal->getAmountNeeded($monthlyIncome),
                    'formatted_amount_needed' => $goal->user->formatCurrency($goal->getAmountNeeded($monthlyIncome)),
                    'monthly_income' => $monthlyIncome,
                    'formatted_monthly_income' => $goal->user->formatCurrency($monthlyIncome),
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching savings goal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch savings goal',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update the specified savings goal.
     */
    public function update(Request $request, $id)
    {
        try {
            $goal = SavingsGoal::where('user_id', $request->user()->id)
                ->find($id);

            if (!$goal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Savings goal not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => ['sometimes', 'string', 'max:255'],
                'target_amount' => ['sometimes', 'numeric', 'min:0.01'],
                'target_date' => ['sometimes', 'date', 'after:today'],
                'category' => ['sometimes', 'string', Rule::in(['emergency', 'vacation', 'education', 'home', 'vehicle', 'retirement', 'other'])],
                'priority' => ['sometimes', 'string', Rule::in(['low', 'medium', 'high'])],
                'description' => ['nullable', 'string', 'max:1000'],
                'is_completed' => ['sometimes', 'boolean'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $goal->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Wishlist item updated successfully',
                'data' => $goal
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating savings goal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update savings goal',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified savings goal.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $goal = SavingsGoal::where('user_id', $request->user()->id)
                ->find($id);

            if (!$goal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Savings goal not found'
                ], 404);
            }

            $goal->delete();

            return response()->json([
                'success' => true,
                'message' => 'Wishlist item deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting savings goal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete savings goal',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get savings goal statistics.
     */
    public function statistics(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $monthlyIncome = $request->user()->getTotalIncomeForMonth(now()->month, now()->year);

            $stats = [
                'total_items' => SavingsGoal::where('user_id', $userId)->count(),
                'active_items' => SavingsGoal::where('user_id', $userId)->active()->count(),
                'completed_items' => SavingsGoal::where('user_id', $userId)->completed()->count(),
                'total_value' => SavingsGoal::where('user_id', $userId)->sum('target_amount'),
                'can_afford' => SavingsGoal::where('user_id', $userId)
                    ->where('target_amount', '<=', $monthlyIncome)
                    ->count(),
                'cannot_afford' => SavingsGoal::where('user_id', $userId)
                    ->where('target_amount', '>', $monthlyIncome)
                    ->count(),
                'monthly_income' => $monthlyIncome,
                'formatted_monthly_income' => $request->user()->formatCurrency($monthlyIncome),
                'by_priority' => [
                    'high' => SavingsGoal::where('user_id', $userId)->where('priority', 'high')->count(),
                    'medium' => SavingsGoal::where('user_id', $userId)->where('priority', 'medium')->count(),
                    'low' => SavingsGoal::where('user_id', $userId)->where('priority', 'low')->count(),
                ],
                'by_category' => [],
            ];

            // Get by category
            $categories = SavingsGoal::where('user_id', $userId)
                ->select('category', DB::raw('COUNT(*) as count'), DB::raw('SUM(target_amount) as total_value'))
                ->groupBy('category')
                ->get();

            foreach ($categories as $cat) {
                $stats['by_category'][$cat->category] = [
                    'count' => $cat->count,
                    'total_value' => $cat->total_value,
                    'label' => (new SavingsGoal())->getCategoryLabelAttribute($cat->category),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $stats
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching wishlist statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    private function getCategoryLabelAttribute($category)
    {
        $labels = [
            'emergency' => 'Emergency Fund',
            'vacation' => 'Vacation',
            'education' => 'Education',
            'home' => 'Home',
            'vehicle' => 'Vehicle',
            'retirement' => 'Retirement',
            'other' => 'Other',
        ];
        return $labels[$category] ?? ucfirst($category);
    }
}
