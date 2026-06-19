<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index(Request $request)
    {
        try {
            $categories = Category::where('user_id', $request->user()->id)
                ->orderBy('type')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        try {
            // Validation rules
            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('categories')->where(function ($query) use ($request) {
                        return $query->where('user_id', $request->user()->id)
                            ->where('name', $request->name);
                    })
                ],
                'type' => ['required', 'string', 'in:income,expense'],
                'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ], [
                'name.required' => 'Category name is required',
                'name.unique' => 'You already have a category with this name',
                'name.max' => 'Category name cannot exceed 100 characters',
                'type.required' => 'Category type is required',
                'type.in' => 'Category type must be either income or expense',
                'color.regex' => 'Color must be a valid hex code (e.g., #FF0000)',
            ]);

            // Return validation errors
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create the category
            $category = Category::create([
                'user_id' => $request->user()->id,
                'name' => $request->name,
                'type' => $request->type,
                'color' => $request->color ?? '#6C757D',
                'is_default' => false,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Category created successfully',
                'data' => $category
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
     * Display the specified category.
     */
    public function show(Request $request, $id)
    {
        try {
            $category = Category::where('user_id', $request->user()->id)
                ->find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $category
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch category',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, $id)
    {
        try {
            $category = Category::where('user_id', $request->user()->id)
                ->find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            // Don't allow editing default categories
            if ($category->is_default) {
                return response()->json([
                    'success' => false,
                    'message' => 'Default categories cannot be edited'
                ], 403);
            }

            // Validation rules
            $validator = Validator::make($request->all(), [
                'name' => [
                    'sometimes',
                    'string',
                    'max:100',
                    Rule::unique('categories')->where(function ($query) use ($request, $id) {
                        return $query->where('user_id', $request->user()->id)
                            ->where('name', $request->name)
                            ->where('id', '!=', $id);
                    })
                ],
                'type' => ['sometimes', 'string', 'in:income,expense'],
                'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ], [
                'name.unique' => 'You already have a category with this name',
                'name.max' => 'Category name cannot exceed 100 characters',
                'type.in' => 'Category type must be either income or expense',
                'color.regex' => 'Color must be a valid hex code (e.g., #FF0000)',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update the category
            $category->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully',
                'data' => $category
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified category.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $category = Category::where('user_id', $request->user()->id)
                ->find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found'
                ], 404);
            }

            // Don't allow deleting default categories
            if ($category->is_default) {
                return response()->json([
                    'success' => false,
                    'message' => 'Default categories cannot be deleted'
                ], 403);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Category deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete category',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get categories by type.
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

            $categories = Category::where('user_id', $request->user()->id)
                ->where('type', $type)
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
