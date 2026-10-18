<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class DashboardController
{
    public function show(Request $request)
    {
        $userId = $request->user()->id;
        $statuses = ['Pending', 'Rejected', 'Resolved'];
        $statusFilter = $request->query('status');

        if (!in_array($statusFilter, $statuses, true)) {
            $statusFilter = null;
        }

        $baseQuery = Report::query()->where('reports.users_id', $userId);

        $statusCounts = (clone $baseQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $categoryStats = (clone $baseQuery)
            ->leftJoin('categories', 'reports.categories_id', '=', 'categories.id')
            ->selectRaw("COALESCE(categories.name, ?) as category_name, COUNT(reports.id) as total", ['Belum dikategorikan'])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $facilityStats = (clone $baseQuery)
            ->join('facilities', 'reports.facilities_id', '=', 'facilities.id')
            ->selectRaw('facilities.id, facilities.name as facility_name, COUNT(reports.id) as total')
            ->groupBy('facilities.id', 'facilities.name')
            ->orderByDesc('total')
            ->limit(4)
            ->get();

        $filteredQuery = clone $baseQuery;

        if ($statusFilter !== null) {
            $filteredQuery->where('reports.status', $statusFilter);
        }

        $filteredTotal = (clone $filteredQuery)->count('reports.id');
        $lastPage = $filteredTotal > 0 ? intdiv($filteredTotal - 1, 10) : 0;
        $page = min(max($request->integer('page', 0), 0), $lastPage);

        $reports = $filteredQuery
            ->join('facilities', 'reports.facilities_id', '=', 'facilities.id')
            ->leftJoin('categories', 'reports.categories_id', '=', 'categories.id')
            ->select(
                'reports.*',
                'facilities.name as facilities_name',
                'categories.name as categories_name'
            )
            ->orderByDesc('reports.created_at')
            ->offset($page * 10)
            ->limit(10)
            ->get();

        return view('dashboard.index', [
            '_reports' => $reports,
            '_totalReports' => $filteredTotal,
            '_indexedReports' => $filteredTotal === 0 ? 0 : ($page * 10) + $reports->count(),
            '_currentPage' => $page,
            '_statusFilter' => $statusFilter,
            '_statusCounts' => $statusCounts,
            '_allReportsCount' => (clone $baseQuery)->count(),
            '_categoryStats' => $categoryStats,
            '_facilityStats' => $facilityStats,
            '_maxCategoryReports' => max(1, (int) $categoryStats->max('total')),
        ]);
    }
}
