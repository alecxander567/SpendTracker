<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the expenses.
     */
    public function index(Request $request)
    {
        try {
            $expenses = Expense::where('user_id', $request->user()->id)
                ->with(['category'])
                ->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($expense) {
                    return [
                        'id' => $expense->id,
                        'category_id' => $expense->category_id,
                        'category_name' => $expense->category->name,
                        'category_color' => $expense->category->color,
                        'category_type' => $expense->category->type,
                        'amount' => $expense->amount,
                        'formatted_amount' => $expense->getFormattedAmount(),
                        'description' => $expense->description,
                        'date' => $expense->date,
                        'payment_method' => $expense->payment_method,
                        'payment_method_label' => $expense->getPaymentMethodLabel(),
                        'payment_method_icon' => $expense->getPaymentMethodIcon(),
                        'receipt_image' => $expense->receipt_image,
                        'is_recurring' => $expense->is_recurring,
                        'recurring_frequency' => $expense->recurring_frequency,
                        'recurring_frequency_label' => $expense->getRecurringFrequencyLabel(),
                        'type' => $expense->getTypeLabel(),
                        'type_badge' => $expense->getTypeBadgeClass(),
                        'created_at' => $expense->created_at,
                        'updated_at' => $expense->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $expenses
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch expenses',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created expense.
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
                'description' => ['nullable', 'string', 'max:1000'],
                'date' => ['required', 'date'],
                'payment_method' => ['required', 'string', 'in:cash,credit_card,debit_card,bank_transfer,mobile_money'],
                'receipt_image' => ['nullable', 'string', 'max:255'],
                'is_recurring' => ['sometimes', 'boolean'],
                'recurring_frequency' => ['required_if:is_recurring,true', 'nullable', 'string', 'in:daily,weekly,monthly,yearly'],
            ], [
                'category_id.required' => 'Category is required',
                'category_id.exists' => 'Selected category does not exist',
                'amount.required' => 'Amount is required',
                'amount.min' => 'Amount must be greater than 0',
                'date.required' => 'Date is required',
                'date.date' => 'Invalid date format',
                'payment_method.required' => 'Payment method is required',
                'payment_method.in' => 'Invalid payment method',
                'recurring_frequency.required_if' => 'Recurring frequency is required for recurring expenses',
                'recurring_frequency.in' => 'Recurring frequency must be daily, weekly, monthly, or yearly',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $expense = Expense::create([
                'user_id' => $request->user()->id,
                'category_id' => $request->category_id,
                'amount' => $request->amount,
                'description' => $request->description,
                'date' => $request->date,
                'payment_method' => $request->payment_method,
                'receipt_image' => $request->receipt_image,
                'is_recurring' => $request->is_recurring ?? false,
                'recurring_frequency' => $request->is_recurring ? $request->recurring_frequency : null,
            ]);

            // Load the category relationship
            $expense->load('category');

            return response()->json([
                'success' => true,
                'message' => 'Expense created successfully',
                'data' => $expense
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified expense.
     */
    public function show(Request $request, $id)
    {
        try {
            $expense = Expense::where('user_id', $request->user()->id)
                ->with(['category'])
                ->find($id);

            if (!$expense) {
                return response()->json([
                    'success' => false,
                    'message' => 'Expense not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $expense
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch expense',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update the specified expense.
     */
    public function update(Request $request, $id)
    {
        try {
            $expense = Expense::where('user_id', $request->user()->id)
                ->find($id);

            if (!$expense) {
                return response()->json([
                    'success' => false,
                    'message' => 'Expense not found'
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
                'description' => ['nullable', 'string', 'max:1000'],
                'date' => ['sometimes', 'date'],
                'payment_method' => ['sometimes', 'string', 'in:cash,credit_card,debit_card,bank_transfer,mobile_money'],
                'receipt_image' => ['nullable', 'string', 'max:255'],
                'is_recurring' => ['sometimes', 'boolean'],
                'recurring_frequency' => ['required_if:is_recurring,true', 'nullable', 'string', 'in:daily,weekly,monthly,yearly'],
            ], [
                'amount.min' => 'Amount must be greater than 0',
                'date.date' => 'Invalid date format',
                'payment_method.in' => 'Invalid payment method',
                'recurring_frequency.required_if' => 'Recurring frequency is required for recurring expenses',
                'recurring_frequency.in' => 'Recurring frequency must be daily, weekly, monthly, or yearly',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();

            // Handle recurring frequency
            if (isset($data['is_recurring']) && !$data['is_recurring']) {
                $data['recurring_frequency'] = null;
            }

            $expense->update($data);
            $expense->load('category');

            return response()->json([
                'success' => true,
                'message' => 'Expense updated successfully',
                'data' => $expense
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update expense',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified expense.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $expense = Expense::where('user_id', $request->user()->id)
                ->find($id);

            if (!$expense) {
                return response()->json([
                    'success' => false,
                    'message' => 'Expense not found'
                ], 404);
            }

            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => 'Expense deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete expense',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get expenses by type (income or expense).
     */
    public function getByType(Request $request, $type)
    {
        try {
            if (!in_array($type, ['income', 'expense'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid type. Must be income or expense'
                ], 422);
            }

            $expenses = Expense::where('user_id', $request->user()->id)
                ->whereHas('category', function ($query) use ($type) {
                    $query->where('type', $type);
                })
                ->with(['category'])
                ->orderBy('date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $expenses
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch expenses',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get expenses for a specific month.
     */
    public function getByMonth(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'month' => ['required', 'integer', 'min:1', 'max:12'],
                'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $expenses = Expense::where('user_id', $request->user()->id)
                ->whereMonth('date', $request->month)
                ->whereYear('date', $request->year)
                ->with(['category'])
                ->orderBy('date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $expenses
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch expenses',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get expense summary for a specific month.
     */
    public function getSummary(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'month' => ['sometimes', 'integer', 'min:1', 'max:12'],
                'year' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $month = $request->month ?? now()->month;
            $year = $request->year ?? now()->year;

            $user = $request->user();

            $totalIncome = $user->getTotalIncomeForMonth($month, $year);
            $totalExpenses = $user->getTotalExpensesForMonth($month, $year);

            $expensesByCategory = Expense::where('user_id', $user->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->whereHas('category', function ($query) {
                    $query->where('type', 'expense');
                })
                ->with('category')
                ->selectRaw('category_id, SUM(amount) as total')
                ->groupBy('category_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'category_id' => $item->category_id,
                        'category_name' => $item->category->name,
                        'category_color' => $item->category->color,
                        'total' => $item->total,
                    ];
                });

            $incomeByCategory = Expense::where('user_id', $user->id)
                ->whereMonth('date', $month)
                ->whereYear('date', $year)
                ->whereHas('category', function ($query) {
                    $query->where('type', 'income');
                })
                ->with('category')
                ->selectRaw('category_id, SUM(amount) as total')
                ->groupBy('category_id')
                ->get()
                ->map(function ($item) {
                    return [
                        'category_id' => $item->category_id,
                        'category_name' => $item->category->name,
                        'category_color' => $item->category->color,
                        'total' => $item->total,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'month' => $month,
                    'year' => $year,
                    'total_income' => $totalIncome,
                    'total_expenses' => $totalExpenses,
                    'net_cash_flow' => $totalIncome - $totalExpenses,
                    'expenses_by_category' => $expensesByCategory,
                    'income_by_category' => $incomeByCategory,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch summary',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get recurring expenses.
     */
    public function getRecurring(Request $request)
    {
        try {
            $expenses = Expense::where('user_id', $request->user()->id)
                ->where('is_recurring', true)
                ->with(['category'])
                ->orderBy('date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $expenses
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch recurring expenses',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
