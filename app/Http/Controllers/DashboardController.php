<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $hour = now()->hour;
        $greeting = match (true) {
            $hour < 12 => 'Good morning',
            $hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };

        $nameParts = explode(' ', trim($user->name));
        $initials = strtoupper(
            substr($nameParts[0], 0, 1) . substr(end($nameParts), 0, 1)
        );

        return view('dashboard', [
            'greeting' => $greeting,
            'userInitials' => $initials,
        ]);
    }
}
