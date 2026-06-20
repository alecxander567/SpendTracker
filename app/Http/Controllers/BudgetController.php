<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BudgetController extends Controller
{
    /**
     * Display a listing of the budgets.
     */
    public function index(Request $request)
    {
        try {
            $budgets = Budget::where('user_id', $request->user()->id)
                ->with(['category', 'expenses'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($budget) {
                    return [
                        'id' => $budget->id,
                        'category_id' => $budget->category_id,
                        'category_name' => $budget->category->name,
                        'category_color' => $budget->category->color,
                        'category_type' => $budget->category->type,
                        'amount' => $budget->amount,
                        'period' => $budget->period,
                        'period_label' => $budget->getPeriodLabel(),
                        'start_date' => $budget->start_date,
                        'end_date' => $budget->end_date,
                        'is_active' => $budget->is_active,
                        'spent' => $budget->getSpentAmount(),
                        'remaining' => $budget->getRemainingAmount(),
                        'percentage_used' => round($budget->getPercentageUsed(), 2),
                        'status' => $budget->getStatus(),
                        'status_color' => $budget->getStatusColor(),
                        'expenses_count' => $budget->expenses()->count(),
                        'created_at' => $budget->created_at,
                        'updated_at' => $budget->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $budgets
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch budgets',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created budget.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'category_id' => [
                    'required',
                    'exists:categories,id',
                    Rule::exists('categories', 'id')->where(function ($query) use ($request) {
                        return $query->where('user_id', $request->user()->id);
                    })
                ],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'period' => ['required', 'string', 'in:weekly,monthly,yearly'],
                'start_date' => ['required', 'date'],
                'end_date' => ['nullable', 'date', 'after:start_date'],
                'is_active' => ['sometimes', 'boolean'],
            ], [
                'category_id.required' => 'Category is required',
                'category_id.exists' => 'Selected category does not exist',
                'amount.required' => 'Budget amount is required',
                'amount.min' => 'Budget amount must be greater than 0',
                'period.required' => 'Period is required',
                'period.in' => 'Period must be weekly, monthly, or yearly',
                'start_date.required' => 'Start date is required',
                'start_date.date' => 'Invalid start date format',
                'end_date.after' => 'End date must be after start date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if budget already exists for this category and period
            $existingBudget = Budget::where('user_id', $request->user()->id)
                ->where('category_id', $request->category_id)
                ->where('period', $request->period)
                ->where('start_date', $request->start_date)
                ->first();

            if ($existingBudget) {
                return response()->json([
                    'success' => false,
                    'message' => 'A budget already exists for this category and period',
                    'errors' => [
                        'category_id' => ['Budget already exists for this category and period']
                    ]
                ], 422);
            }

            $budget = Budget::create([
                'user_id' => $request->user()->id,
                'category_id' => $request->category_id,
                'amount' => $request->amount,
                'period' => $request->period,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->is_active ?? true,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Budget created successfully',
                'data' => $budget->load('category')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in store: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified budget.
     */
    public function show(Request $request, $id)
    {
        try {
            $budget = Budget::where('user_id', $request->user()->id)
                ->with(['category', 'expenses'])
                ->find($id);

            if (!$budget) {
                return response()->json([
                    'success' => false,
                    'message' => 'Budget not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $budget
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in show: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch budget',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update the specified budget.
     */
    public function update(Request $request, $id)
    {
        try {
            $budget = Budget::where('user_id', $request->user()->id)
                ->find($id);

            if (!$budget) {
                return response()->json([
                    'success' => false,
                    'message' => 'Budget not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'category_id' => [
                    'sometimes',
                    'exists:categories,id',
                    Rule::exists('categories', 'id')->where(function ($query) use ($request) {
                        return $query->where('user_id', $request->user()->id);
                    })
                ],
                'amount' => ['sometimes', 'numeric', 'min:0.01'],
                'period' => ['sometimes', 'string', 'in:weekly,monthly,yearly'],
                'start_date' => ['sometimes', 'date'],
                'end_date' => ['nullable', 'date', 'after:start_date'],
                'is_active' => ['sometimes', 'boolean'],
            ], [
                'amount.min' => 'Budget amount must be greater than 0',
                'period.in' => 'Period must be weekly, monthly, or yearly',
                'end_date.after' => 'End date must be after start date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $budget->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Budget updated successfully',
                'data' => $budget->load('category')
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in update: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update budget',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified budget.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $budget = Budget::where('user_id', $request->user()->id)
                ->find($id);

            if (!$budget) {
                return response()->json([
                    'success' => false,
                    'message' => 'Budget not found'
                ], 404);
            }

            $budget->delete();

            return response()->json([
                'success' => true,
                'message' => 'Budget deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete budget',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get active budgets.
     */
    public function getActive(Request $request)
    {
        try {
            $budgets = Budget::where('user_id', $request->user()->id)
                ->where('is_active', true)
                ->with(['category'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $budgets
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in getActive: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active budgets',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get active budgets for dropdown selection.
     */
    public function getActiveBudgets(Request $request)
    {
        try {
            // Check if user is authenticated
            if (!$request->user()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $budgets = Budget::where('user_id', $request->user()->id)
                ->where('is_active', true)
                ->with(['category'])
                ->get()
                ->map(function ($budget) {
                    return [
                        'id' => $budget->id,
                        'category_id' => $budget->category_id,
                        'category_name' => $budget->category->name,
                        'category_color' => $budget->category->color,
                        'amount' => $budget->amount,
                        'remaining' => $budget->getRemainingAmount(),
                        'period' => $budget->period,
                        'period_label' => $budget->getPeriodLabel(),
                        'start_date' => $budget->start_date,
                        'end_date' => $budget->end_date,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $budgets
            ], 200);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error in getActiveBudgets: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch active budgets',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get budget summary with spending.
     */
    public function getSummary(Request $request)
    {
        try {
            $budgets = Budget::where('user_id', $request->user()->id)
                ->where('is_active', true)
                ->with(['category'])
                ->get();

            $summary = $budgets->map(function ($budget) {
                $spent = $budget->getSpentAmount();
                $percentage = $budget->getPercentageUsed();

                return [
                    'budget_id' => $budget->id,
                    'category_name' => $budget->category->name,
                    'category_color' => $budget->category->color,
                    'budgeted' => $budget->amount,
                    'spent' => $spent,
                    'remaining' => $budget->getRemainingAmount(),
                    'percentage' => round($percentage, 2),
                    'status' => $budget->getStatus(),
                    'status_color' => $budget->getStatusColor(),
                    'period' => $budget->period,
                    'start_date' => $budget->start_date,
                    'end_date' => $budget->end_date,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'budgets' => $summary,
                    'summary' => [
                        'total_budgeted' => $budgets->sum('amount'),
                        'total_spent' => $budgets->sum(fn($b) => $b->getSpentAmount()),
                        'total_remaining' => $budgets->sum('amount') - $budgets->sum(fn($b) => $b->getSpentAmount()),
                        'over_budget' => $budgets->filter(fn($b) => $b->getStatus() === 'exceeded')->count(),
                        'on_track' => $budgets->filter(fn($b) => $b->getStatus() === 'good')->count(),
                        'warning' => $budgets->filter(fn($b) => $b->getStatus() === 'warning')->count(),
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in getSummary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch budget summary',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
