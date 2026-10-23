<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Resolves which sidebar item should be highlighted for the current request.
 *
 * Previously each nav item tested the URL independently, which left gaps:
 * report detail and report edit matched nothing at all, while a per-report
 * discussion always highlighted "Diskusi" even when the user arrived from the
 * dashboard. Resolving a single section here keeps the highlight predictable:
 * exactly one item is ever active.
 */
class SidebarNavigation
{
    public const DASHBOARD = 'dashboard';

    public const DISCUSSION = 'discussion';

    public const ANALYTICS = 'analytics';

    public const REPORTS = 'reports';

    public const SETTINGS = 'settings';

    public const ADMIN_USERS = 'admin.users';

    public const ADMIN_FACILITIES = 'admin.facilities';

    public const ADMIN_CATEGORIES = 'admin.categories';

    /**
     * The section whose nav item should be highlighted.
     *
     * Report pages belong to the dashboard: reports are opened from a list
     * there, and the report itself has no sidebar entry of its own.
     */
    public function activeSection(Request $request): string
    {
        // Admin master-data pages.
        if ($request->is('admin/users*')) {
            return self::ADMIN_USERS;
        }

        if ($request->is('admin/facilities*')) {
            return self::ADMIN_FACILITIES;
        }

        if ($request->is('admin/categories*')) {
            return self::ADMIN_CATEGORIES;
        }

        if ($request->is('settings*')) {
            return self::SETTINGS;
        }

        if ($request->is('analytics*')) {
            return self::ANALYTICS;
        }

        // A report discussion keeps the origin it was opened from: the
        // discussions list passes ?from=discussions, everything else (report
        // detail, dashboard) stays on the dashboard.
        if ($request->is('reports/*/discussion')) {
            return $request->query('from') === 'discussions'
                ? self::DISCUSSION
                : self::DASHBOARD;
        }

        if ($request->is('discussions*')) {
            return self::DISCUSSION;
        }

        // Creating a report is its own nav item; viewing or editing one is part
        // of the dashboard flow.
        if ($request->is('reports/create')) {
            return self::REPORTS;
        }

        return self::DASHBOARD;
    }

    /** Whether the given section should render as active. */
    public function isActive(Request $request, string $section): bool
    {
        return $this->activeSection($request) === $section;
    }
}
