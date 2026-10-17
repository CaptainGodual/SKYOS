<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Flight;
use App\Models\Pirep;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'pilots' => User::query()->where('is_admin', false)->count(),
            'flights' => Flight::query()->count(),
            'open_flights' => Flight::query()->where('status', 'Open')->count(),
            'pending_pireps' => Pirep::query()->where('status', 'Pending review')->count(),
            'approved_pireps' => Pirep::query()->whereIn('status', Pirep::ACCEPTED_STATUSES)->count(),
        ];

        $recentPireps = Pirep::query()->latest()->take(5)->get();

        return view('admin.dashboard', [
            'stats' => $stats,
            'recentPireps' => $recentPireps,
        ]);
    }
}
