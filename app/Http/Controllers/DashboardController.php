<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController
{
    private const STATUSES = ['Pending', 'Rejected', 'Resolved'];

    private const SEVERITIES = ['Low', 'Medium', 'High', 'Critical'];

    private const SORT_OPTIONS = ['newest', 'oldest', 'user_asc', 'user_desc', 'severity_high', 'severity_low'];

    public function show(Request $request)
    {
        $user = $request->user();
        $isStaff = $user->isStaff();

        $statusFilter = $request->query('status');
        $statusFilter = in_array($statusFilter, self::STATUSES, true) ? $statusFilter : null;

        $search = $isStaff ? trim((string) $request->query('q', '')) : '';
        $search = mb_substr($search, 0, 255);

        $reporterFilter = $isStaff && ctype_digit((string) $request->query('reporter', ''))
            ? (string) $request->query('reporter')
            : null;
        $severityFilter = $isStaff && in_array($request->query('severity'), self::SEVERITIES, true)
            ? $request->query('severity')
            : null;
        $facilityFilter = $isStaff && ctype_digit((string) $request->query('facility', ''))
            ? (string) $request->query('facility')
            : null;
        $categoryFilter = $isStaff && ctype_digit((string) $request->query('category', ''))
            ? (string) $request->query('category')
            : null;
        $sortFilter = $isStaff && in_array($request->query('sort'), self::SORT_OPTIONS, true)
            ? $request->query('sort')
            : 'newest';

        // Staff can view all reports. Students remain scoped to their own reports.
        $baseQuery = Report::query();
        if (! $isStaff) {
            $baseQuery->where('reports.users_id', $user->id);
        }

        $statusCounts = (clone $baseQuery)
            ->selectRaw('reports.status, COUNT(reports.id) as total')
            ->groupBy('reports.status')
            ->pluck('total', 'status');

        $categoryStats = (clone $baseQuery)
            ->leftJoin('categories', 'reports.categories_id', '=', 'categories.id')
            ->selectRaw('COALESCE(categories.name, ?) as category_name, COUNT(reports.id) as total', ['Belum dikategorikan'])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total')
            ->get();

        $facilityStats = (clone $baseQuery)
            ->join('facilities', 'reports.facilities_id', '=', 'facilities.id')
            ->selectRaw('facilities.id, facilities.name as facility_name, COUNT(reports.id) as total')
            ->groupBy('facilities.id', 'facilities.name')
            ->orderByDesc('total')
            ->get();

        $filteredQuery = clone $baseQuery;

        if ($statusFilter !== null) {
            $filteredQuery->where('reports.status', $statusFilter);
        }

        if ($isStaff) {
            if ($reporterFilter !== null) {
                $filteredQuery->where('reports.users_id', $reporterFilter);
            }

            if ($severityFilter !== null) {
                $filteredQuery->where('reports.severity', $severityFilter);
            }

            if ($facilityFilter !== null) {
                $filteredQuery->where('reports.facilities_id', $facilityFilter);
            }

            if ($categoryFilter !== null) {
                $filteredQuery->where('reports.categories_id', $categoryFilter);
            }

            if ($search !== '') {
                $filteredQuery->where(function (Builder $query) use ($search) {
                    $query->where('reports.title', 'like', '%'.$search.'%')
                        ->orWhere('reports.location_description', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                            $userQuery->where('name', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        });

                    if (ctype_digit($search)) {
                        $query->orWhere('reports.id', (int) $search);
                    }
                });
            }
        }

        $filteredTotal = (clone $filteredQuery)->count('reports.id');
        $lastPage = $filteredTotal > 0 ? intdiv($filteredTotal - 1, 10) : 0;
        $page = min(max($request->integer('page', 0), 0), $lastPage);

        $reportsQuery = (clone $filteredQuery)
            ->with(['user', 'facility', 'category'])
            ->withMax('responses', 'created_at');

        if ($isStaff) {
            switch ($sortFilter) {
                case 'oldest':
                    $reportsQuery->orderBy('reports.created_at');
                    break;
                case 'user_asc':
                    $reportsQuery
                        ->orderBy(User::query()->select('name')->whereColumn('users.id', 'reports.users_id'))
                        ->orderByDesc('reports.created_at');
                    break;
                case 'user_desc':
                    $reportsQuery
                        ->orderByDesc(User::query()->select('name')->whereColumn('users.id', 'reports.users_id'))
                        ->orderByDesc('reports.created_at');
                    break;
                case 'severity_high':
                    $reportsQuery
                        ->orderByRaw("CASE reports.severity WHEN 'Critical' THEN 4 WHEN 'High' THEN 3 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 1 ELSE 0 END DESC")
                        ->orderByDesc('reports.created_at');
                    break;
                case 'severity_low':
                    $reportsQuery
                        ->orderByRaw("CASE reports.severity WHEN 'Low' THEN 1 WHEN 'Medium' THEN 2 WHEN 'High' THEN 3 WHEN 'Critical' THEN 4 ELSE 5 END ASC")
                        ->orderByDesc('reports.created_at');
                    break;
                default:
                    $reportsQuery->orderByRaw('COALESCE(responses_max_created_at, reports.updated_at, reports.created_at) DESC');
                    break;
            }
        } else {
            $reportsQuery->orderByDesc('reports.created_at');
        }

        $reports = $reportsQuery
            ->offset($page * 10)
            ->limit(10)
            ->get();

        $viewData = [
            '_isStaff' => $isStaff,
            '_reports' => $reports,
            '_totalReports' => $filteredTotal,
            '_indexedReports' => $filteredTotal === 0 ? 0 : ($page * 10) + $reports->count(),
            '_currentPage' => $page,
            '_statusFilter' => $statusFilter,
            '_statusCounts' => $statusCounts,
            '_allReportsCount' => (clone $baseQuery)->count('reports.id'),
            '_categoryStats' => $categoryStats,
            '_facilityStats' => $facilityStats,
            '_maxCategoryReports' => max(1, (int) $categoryStats->max('total')),
            '_search' => $search,
            '_reporterFilter' => $reporterFilter,
            '_severityFilter' => $severityFilter,
            '_facilityFilter' => $facilityFilter,
            '_categoryFilter' => $categoryFilter,
            '_sortFilter' => $sortFilter,
            '_hasFilters' => $statusFilter !== null
                || $search !== ''
                || $reporterFilter !== null
                || $severityFilter !== null
                || $facilityFilter !== null
                || $categoryFilter !== null
                || ($isStaff && $sortFilter !== 'newest'),
        ];

        if ($isStaff) {
            $viewData['_reporters'] = User::query()
                ->whereIn('id', Report::query()->select('users_id')->distinct())
                ->orderBy('name')
                ->get(['id', 'name']);
            $viewData['_facilities'] = Facility::query()->orderBy('name')->get(['id', 'name']);
            $viewData['_categories'] = Category::query()->orderBy('name')->get(['id', 'name']);

            return view('dashboard.staff', $viewData);
        }

        return view('dashboard.index', $viewData);
    }
}
