<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class IncomeController extends Controller
{
    /**
     * Display a listing of the user's incomes.
     */
    public function index(Request $request)
    {
        $query = $request->user()->incomes()->with('category');

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->forDateRange($request->start_date, $request->end_date);
        }

        // Filter by month/year
        if ($request->has('month') && $request->has('year')) {
            $query->forMonth($request->month, $request->year);
        }

        // Filter by payment method
        if ($request->has('payment_method')) {
            $query->paymentMethod($request->payment_method);
        }

        // Filter by source
        if ($request->has('source')) {
            $query->source($request->source);
        }

        // Filter by recurring status
        if ($request->has('is_recurring')) {
            if ($request->is_recurring) {
                $query->recurring();
            } else {
                $query->nonRecurring();
            }
        }

        // Filter by active status
        if ($request->has('is_active')) {
            if ($request->is_active) {
                $query->active();
            } else {
                $query->inactive();
            }
        }

        // Sort
        $sortField = $request->get('sort', 'date');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $incomes = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $incomes,
            'message' => 'Incomes retrieved successfully'
        ]);
    }

    /**
     * Store a newly created income.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:categories,id',
            'source' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,direct_deposit,check,mobile_money,crypto,other',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|required_if:is_recurring,true|in:daily,weekly,biweekly,monthly,quarterly,yearly',
            'recurring_end_date' => 'nullable|date|after:date',
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle receipt image upload
        $receiptImage = null;
        if ($request->hasFile('receipt_image')) {
            $receiptImage = $request->file('receipt_image')->store('income-receipts', 'public');
        }

        $income = $request->user()->incomes()->create([
            'category_id' => $request->category_id,
            'source' => $request->source,
            'amount' => $request->amount,
            'description' => $request->description,
            'date' => $request->date,
            'payment_method' => $request->payment_method,
            'is_recurring' => $request->is_recurring ?? false,
            'recurring_frequency' => $request->recurring_frequency,
            'recurring_end_date' => $request->recurring_end_date,
            'receipt_image' => $receiptImage,
            'is_active' => $request->is_active ?? true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $income->load('category'),
            'message' => 'Income created successfully'
        ], 201);
    }

    /**
     * Display the specified income.
     */
    public function show(Request $request, $id)
    {
        $income = $request->user()->incomes()->with('category')->find($id);

        if (!$income) {
            return response()->json([
                'success' => false,
                'message' => 'Income not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $income,
            'message' => 'Income retrieved successfully'
        ]);
    }

    /**
     * Update the specified income.
     */
    public function update(Request $request, $id)
    {
        $income = $request->user()->incomes()->find($id);

        if (!$income) {
            return response()->json([
                'success' => false,
                'message' => 'Income not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:categories,id',
            'source' => 'sometimes|string|max:100',
            'amount' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'date' => 'sometimes|date',
            'payment_method' => 'sometimes|in:cash,bank_transfer,direct_deposit,check,mobile_money,crypto,other',
            'is_recurring' => 'boolean',
            'recurring_frequency' => 'nullable|required_if:is_recurring,true|in:daily,weekly,biweekly,monthly,quarterly,yearly',
            'recurring_end_date' => 'nullable|date|after:date',
            'receipt_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle receipt image upload
        if ($request->hasFile('receipt_image')) {
            // Delete old receipt if exists
            if ($income->receipt_image) {
                Storage::disk('public')->delete($income->receipt_image);
            }
            $receiptImage = $request->file('receipt_image')->store('income-receipts', 'public');
            $income->receipt_image = $receiptImage;
        }

        // Update income fields
        $income->fill($request->only([
            'category_id',
            'source',
            'amount',
            'description',
            'date',
            'payment_method',
            'is_recurring',
            'recurring_frequency',
            'recurring_end_date',
            'is_active'
        ]));

        $income->save();

        return response()->json([
            'success' => true,
            'data' => $income->load('category'),
            'message' => 'Income updated successfully'
        ]);
    }

    /**
     * Remove the specified income.
     */
    public function destroy(Request $request, $id)
    {
        $income = $request->user()->incomes()->find($id);

        if (!$income) {
            return response()->json([
                'success' => false,
                'message' => 'Income not found'
            ], 404);
        }

        // Delete receipt image if exists
        if ($income->receipt_image) {
            Storage::disk('public')->delete($income->receipt_image);
        }

        $income->delete();

        return response()->json([
            'success' => true,
            'message' => 'Income deleted successfully'
        ]);
    }

    /**
     * Get income summary for the current month.
     */
    public function summary(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $totalIncome = $request->user()->getTotalIncomeForMonth($month, $year);

        $incomeBreakdown = $request->user()->getMonthlyIncomeBreakdown($month, $year);

        // Get income by payment method
        $paymentMethodBreakdown = $request->user()->incomes()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('is_active', true)
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->orderBy('total', 'desc')
            ->get();

        // Get recurring vs one-time
        $recurringStats = $request->user()->incomes()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('is_active', true)
            ->select(
                'is_recurring',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('is_recurring')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'month' => $month,
                    'year' => $year,
                    'month_name' => now()->month($month)->format('F'),
                ],
                'total_income' => $totalIncome,
                'formatted_total' => $request->user()->formatCurrency($totalIncome),
                'income_sources' => $incomeBreakdown,
                'payment_methods' => $paymentMethodBreakdown,
                'recurring_stats' => $recurringStats,
                'total_entries' => $request->user()->incomes()
                    ->whereMonth('date', $month)
                    ->whereYear('date', $year)
                    ->where('is_active', true)
                    ->count(),
            ],
            'message' => 'Income summary retrieved successfully'
        ]);
    }

    /**
     * Get recurring incomes.
     */
    public function recurring(Request $request)
    {
        $incomes = $request->user()
            ->incomes()
            ->with('category')
            ->recurring()
            ->active()
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $incomes,
            'message' => 'Recurring incomes retrieved successfully'
        ]);
    }

    /**
     * Get income sources list.
     */
    public function sources(Request $request)
    {
        $sources = $request->user()->getIncomeSources();

        return response()->json([
            'success' => true,
            'data' => $sources,
            'message' => 'Income sources retrieved successfully'
        ]);
    }

    /**
     * Get income statistics.
     */
    public function statistics(Request $request)
    {
        $year = $request->get('year', now()->year);

        // Monthly income for the year
        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $total = $request->user()->getTotalIncomeForMonth($month, $year);
            $monthlyData[] = [
                'month' => $month,
                'month_name' => now()->month($month)->format('M'),
                'total' => $total,
                'formatted' => $request->user()->formatCurrency($total),
            ];
        }

        // Top income sources for the year
        $topSources = $request->user()->incomes()
            ->whereYear('date', $year)
            ->where('is_active', true)
            ->select('source', DB::raw('SUM(amount) as total'))
            ->groupBy('source')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        // Average monthly income
        $averageMonthly = $request->user()->incomes()
            ->whereYear('date', $year)
            ->where('is_active', true)
            ->select(DB::raw('AVG(monthly_total) as average'))
            ->from(DB::raw('(
                SELECT SUM(amount) as monthly_total
                FROM incomes
                WHERE user_id = ' . $request->user()->id . '
                AND YEAR(date) = ' . $year . '
                AND is_active = 1
                GROUP BY MONTH(date)
            ) as monthly_totals'))
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'monthly_breakdown' => $monthlyData,
                'top_sources' => $topSources,
                'average_monthly_income' => $averageMonthly->average ?? 0,
                'formatted_average' => $request->user()->formatCurrency($averageMonthly->average ?? 0),
                'total_annual_income' => $request->user()->incomes()
                    ->whereYear('date', $year)
                    ->where('is_active', true)
                    ->sum('amount'),
            ],
            'message' => 'Income statistics retrieved successfully'
        ]);
    }

    /**
     * Toggle income active status.
     */
    public function toggleActive(Request $request, $id)
    {
        $income = $request->user()->incomes()->find($id);

        if (!$income) {
            return response()->json([
                'success' => false,
                'message' => 'Income not found'
            ], 404);
        }

        $income->is_active = !$income->is_active;
        $income->save();

        return response()->json([
            'success' => true,
            'data' => $income,
            'message' => 'Income status toggled successfully'
        ]);
    }

    /**
     * Bulk delete incomes.
     */
    public function bulkDelete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:incomes,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $deleted = $request->user()->incomes()
            ->whereIn('id', $request->ids)
            ->delete();

        return response()->json([
            'success' => true,
            'data' => [
                'deleted_count' => $deleted,
            ],
            'message' => 'Incomes deleted successfully'
        ]);
    }

    /**
     * Export incomes to CSV.
     */
    public function export(Request $request)
    {
        $incomes = $request->user()
            ->incomes()
            ->with('category')
            ->where('is_active', true)
            ->orderBy('date', 'desc')
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="incomes_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($incomes) {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, [
                'ID',
                'Source',
                'Amount',
                'Category',
                'Description',
                'Date',
                'Payment Method',
                'Is Recurring',
                'Frequency',
                'Status'
            ]);

            // Add data
            foreach ($incomes as $income) {
                fputcsv($file, [
                    $income->id,
                    $income->source,
                    $income->amount,
                    $income->category ? $income->category->name : 'Uncategorized',
                    $income->description,
                    $income->date->format('Y-m-d'),
                    $income->getPaymentMethodLabel(),
                    $income->is_recurring ? 'Yes' : 'No',
                    $income->getRecurringFrequencyLabel(),
                    $income->is_active ? 'Active' : 'Inactive'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
