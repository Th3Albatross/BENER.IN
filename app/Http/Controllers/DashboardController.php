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
        $reqPage = $request->integer('page');
        $totalReports = Report::where('users_id', $request->user()->id)
                            ->count();
        $pageCount = ceil($totalReports / 10);

        if ($reqPage > $pageCount || $reqPage < 0)
            return redirect()->to(
                request()->url() . '?page=' . max(0, min($reqPage, $pageCount))
            );

        // stuff to cap the max page
        $page = min($reqPage, max(0, $pageCount - 1));
        $reports = Report::where('users_id', $request->user()->id)
                    ->offset(10 * $page)
                    ->limit(10)
                    ->join('facilities', 'reports.facilities_id', '=', 'facilities.id')
                    ->join('categories', 'reports.categories_id', '=', 'categories.id')
                    ->select('reports.*', 'facilities.name as facilities_name', 'categories.name as categories_name')
                    ->orderBy('updated_at', 'desc')
                    ->get();

        return view('dashboard.index', [
            '_reports' => $reports,
            '_totalReports' => $totalReports,
            '_indexedReports' => count($reports) + $page * 10
        ]);
    }
}
