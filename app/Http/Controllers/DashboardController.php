<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Facility;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\Report;

class DashboardController
{

    // single page contains 10 entries
    public function show(Request $request) {
        $totalReports = Report::where('users_id', $request->user()->id)
                            ->count();

        // stuff to cap the max page
        $page = min($request->query('page') ?? 0, max(0, ceil($totalReports / 10) - 1));
        $reports = Report::where('users_id', $request->user()->id)
                    ->offset(10 * $page)
                    ->limit(10)
                    ->join('facilities', 'reports.facilities_id', '=', 'facilities.id')
                    ->join('categories', 'reports.categories_id', '=', 'categories.id')
                    ->select('reports.*', 'facilities.name as facilities_name', 'categories.name as categories_name')
                    ->orderBy('updated_at')
                    ->get();

        return view('dashboard.index', [
            '_reports' => $reports,
            '_totalReports' => $totalReports
        ]);
    }
}
