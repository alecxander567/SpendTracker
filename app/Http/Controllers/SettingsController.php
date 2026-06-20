<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /**
     * Get user profile settings.
     */
    public function profile(Request $request)
    {
        try {
            $user = $request->user();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'currency' => $user->currency,
                    'timezone' => $user->timezone,
                    'budget_cycle_start' => $user->budget_cycle_start,
                    'preferences' => $user->preferences,
                    'currency_symbol' => $user->getCurrencySymbol(),
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching profile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile settings',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'name' => ['sometimes', 'string', 'max:255'],
                'email' => [
                    'sometimes',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id),
                ],
                'currency' => ['sometimes', 'string', 'in:USD,EUR,GBP,JPY,PHP,AUD,CAD'],
                'timezone' => ['sometimes', 'string', 'timezone'],
                'budget_cycle_start' => ['nullable', 'date'],
                'preferences' => ['sometimes', 'array'],
            ], [
                'name.max' => 'Name cannot exceed 255 characters',
                'email.unique' => 'This email is already taken',
                'email.email' => 'Please enter a valid email address',
                'currency.in' => 'Invalid currency selected',
                'timezone.timezone' => 'Invalid timezone selected',
                'budget_cycle_start.date' => 'Invalid date format',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->only([
                'name',
                'email',
                'currency',
                'timezone',
                'budget_cycle_start',
                'preferences'
            ]);

            // Filter out null values
            $data = array_filter($data, function ($value) {
                return $value !== null;
            });

            $user->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'currency' => $user->currency,
                    'timezone' => $user->timezone,
                    'budget_cycle_start' => $user->budget_cycle_start,
                    'preferences' => $user->preferences,
                    'currency_symbol' => $user->getCurrencySymbol(),
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating profile: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'current_password' => ['required', 'string'],
                'new_password' => ['required', 'string', 'min:8', 'confirmed'],
            ], [
                'current_password.required' => 'Current password is required',
                'new_password.required' => 'New password is required',
                'new_password.min' => 'New password must be at least 8 characters',
                'new_password.confirmed' => 'Password confirmation does not match',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if current password is correct
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                    'errors' => [
                        'current_password' => ['The current password is incorrect']
                    ]
                ], 422);
            }

            // Update password
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating password: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update password',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update user preferences.
     */
    public function updatePreferences(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'preferences' => ['required', 'array'],
                'preferences.dark_mode' => ['sometimes', 'boolean'],
                'preferences.notifications' => ['sometimes', 'boolean'],
                'preferences.language' => ['sometimes', 'string', 'in:en,es,fr,de,ja,zh'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Merge existing preferences with new ones
            $currentPreferences = $user->preferences ?? [];
            $newPreferences = array_merge($currentPreferences, $request->preferences);

            $user->preferences = $newPreferences;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Preferences updated successfully',
                'data' => [
                    'preferences' => $user->preferences
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error updating preferences: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update preferences',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get available currencies.
     */
    public function getCurrencies()
    {
        try {
            $currencies = [
                ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
                ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
                ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
                ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥'],
                ['code' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => '₱'],
                ['code' => 'AUD', 'name' => 'Australian Dollar', 'symbol' => 'A$'],
                ['code' => 'CAD', 'name' => 'Canadian Dollar', 'symbol' => 'C$'],
            ];

            return response()->json([
                'success' => true,
                'data' => $currencies
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch currencies',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get available timezones.
     */
    public function getTimezones()
    {
        try {
            $timezones = [
                'UTC' => 'UTC',
                'America/New_York' => 'Eastern Time (US & Canada)',
                'America/Chicago' => 'Central Time (US & Canada)',
                'America/Denver' => 'Mountain Time (US & Canada)',
                'America/Los_Angeles' => 'Pacific Time (US & Canada)',
                'America/Anchorage' => 'Alaska',
                'America/Halifax' => 'Atlantic Time (Canada)',
                'America/Phoenix' => 'Arizona',
                'America/Toronto' => 'Eastern Time (Canada)',
                'Europe/London' => 'London',
                'Europe/Paris' => 'Paris',
                'Europe/Berlin' => 'Berlin',
                'Europe/Moscow' => 'Moscow',
                'Asia/Dubai' => 'Dubai',
                'Asia/Kolkata' => 'India',
                'Asia/Shanghai' => 'China',
                'Asia/Tokyo' => 'Japan',
                'Asia/Manila' => 'Philippines',
                'Asia/Singapore' => 'Singapore',
                'Asia/Hong_Kong' => 'Hong Kong',
                'Australia/Sydney' => 'Sydney',
                'Pacific/Auckland' => 'Auckland',
            ];

            return response()->json([
                'success' => true,
                'data' => $timezones
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch timezones',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Delete user account.
     */
    public function deleteAccount(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'password' => ['required', 'string'],
                'confirm' => ['required', 'accepted'],
            ], [
                'password.required' => 'Password is required to delete account',
                'confirm.accepted' => 'Please confirm you want to delete your account',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Password is incorrect',
                    'errors' => [
                        'password' => ['The password is incorrect']
                    ]
                ], 422);
            }

            // Log the user out
            Auth::logout();

            // Delete the user account
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting account: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
