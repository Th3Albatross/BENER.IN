@extends('layouts.app')

@push('styles')
    @vite('resources/css/dashboard.css')
@endpush

@section('title', 'Dashboard Petugas')
@section('page-title', 'Dashboard')
@section('page-description', 'Facility Status & Maintenance Reports')

@section('content')
<div class="dashboard-widget">
    <section class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-label">TOTAL REPORTS</span>
                <span class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <rect x="5" y="3" width="14" height="18" rx="2"/>
                        <path d="M9 7h6M9 11h6M9 15h4"/>
                    </svg>
                </span>
            </div>
            <div class="stat-card-body">
                <span class="stat-value">24</span>
                <span class="stat-description">Logged this month</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-label">REJECTED</span>
                <span class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="8"/>
                        <path d="M9 9l6 6M15 9l-6 6"/>
                    </svg>
                </span>
            </div>
            <div class="stat-card-body">
                <span class="stat-value">8</span>
                <span class="stat-description">Awaiting assignment</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-label">IN PROGRESS</span>
                <span class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="8"/>
                        <path d="M12 8v4l2.5 2"/>
                    </svg>
                </span>
            </div>
            <div class="stat-card-body">
                <span class="stat-value">12</span>
                <span class="stat-description">Assigned to contractors</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-label">RESOLVED</span>
                <span class="stat-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <circle cx="12" cy="12" r="8"/>
                        <path d="m8.5 12 2.3 2.3 4.7-4.7"/>
                    </svg>
                </span>
            </div>
            <div class="stat-card-body">
                <span class="stat-value">4</span>
                <span class="stat-description">Completed &amp; closed</span>
            </div>
        </div>
    </section>

    <section class="dashboard-main-grid">
        <div class="dashboard-card reports-card">
            <div class="dashboard-card-header">
                <h2 class="dashboard-card-title">Recent Facility Reports</h2>

                <button type="button" class="filter-button">
                    <span>Filter: All</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </button>
            </div>

            <div class="reports-table-wrapper">
                <table class="reports-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>FACILITY NAME</th>
                            <th>ISSUE TYPE</th>
                            <th>STATUS</th>
                            <th>SUBMITTED</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($_reports as $report)
                            <tr>
                                <td class="report-id">{{ $report['id'] }}</td>
                                <td class="facility-name">{{ $report['facilities_name'] }}</td>
                                <td>{{ $report['categories_name'] }}</td>
                                <td><span class="status-badge status-progress">{{ $report['status'] }}</span></td>
                                <td>{{ $report['created_at'] }}</td>
                                @if ($report['status'] == 'Pending')
                                    <td>
                                        <a href="{{ url('/reports/' . $report['identifier'] . '/edit') }}" class="report-action">Edit</a>
                                    </td>
                                @else
                                    <td>
                                        <a href="{{ url('/reports/' . $report['identifier']) }}" class="report-action">View</a>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="reports-footer">
                <span>Showing {{ $_indexedReports }} of {{ $_totalReports }} reports</span>

                <!--
                <div class="pagination">
                    <form method="GET" action="{{ url()->current() }}">
                        @csrf
                        <input type="hidden" name="page" value="{{ intval(url()->query('page') ?? 0, 10) + 1}}">
                        <input type="submit" class="pagination-button" value="Previous">
                        <input type="submit" class="pagination-button" value="Next">
                    </form>
                    <button type="button" class="pagination-button" disabled>Previous</button>
                    <button type="button" class="pagination-button">Next</button>
                </div>
                -->

                <div class="pagination">
                    <form method="GET" action="{{ url()->current() }}">
                        @if (request()->integer('page') >= 1)
                            <button
                                type="submit"
                                name="page"
                                value="{{ request()->integer('page', 1) - 1 }}"
                                class="pagination-button">
                                Previous
                            </button>
                        @endif

                        @if (count($_reports) == 10)
                        <button
                            type="submit"
                            name="page"
                            value="{{ request()->integer('page', 1) + 1 }}"
                            class="pagination-button">
                            Next
                        </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        <aside class="dashboard-side">
            <div class="dashboard-card quick-actions-card">
                <div class="dashboard-card-header">
                    <h2 class="dashboard-card-title">Quick Actions</h2>
                </div>

                <a href="{{ url('/reports/create') }}" class="quick-action-button">
                    <span class="quick-action-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                    </span>

                    <span>Submit New Report</span>

                    <svg class="quick-action-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="m9 18 6-6-6-6"/>
                    </svg>
                </a>
            </div>

            <div class="dashboard-card category-card">
                <div class="dashboard-card-header">
                    <h2 class="dashboard-card-title">Reports by Category</h2>
                </div>

                <div class="category-list">
                    <div class="category-item">
                        <div class="category-info">
                            <span>Electrical</span>
                            <strong>8</strong>
                        </div>
                        <div class="category-progress">
                            <div class="category-progress-fill" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="category-item">
                        <div class="category-info">
                            <span>Plumbing</span>
                            <strong>6</strong>
                        </div>
                        <div class="category-progress">
                            <div class="category-progress-fill" style="width: 75%"></div>
                        </div>
                    </div>

                    <div class="category-item">
                        <div class="category-info">
                            <span>HVAC</span>
                            <strong>4</strong>
                        </div>
                        <div class="category-progress">
                            <div class="category-progress-fill" style="width: 50%"></div>
                        </div>
                    </div>

                    <div class="category-item">
                        <div class="category-info">
                            <span>Structural</span>
                            <strong>3</strong>
                        </div>
                        <div class="category-progress">
                            <div class="category-progress-fill" style="width: 37.5%"></div>
                        </div>
                    </div>

                    <div class="category-item">
                        <div class="category-info">
                            <span>Other</span>
                            <strong>3</strong>
                        </div>
                        <div class="category-progress">
                            <div class="category-progress-fill" style="width: 37.5%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-card facilities-card">
                <div class="dashboard-card-header">
                    <h2 class="dashboard-card-title">Frequently Reported Facilities</h2>
                </div>

                <div class="facility-list">
                    <div class="facility-item">
                        <div class="facility-rank">01</div>
                        <div class="facility-details">
                            <span class="facility-title">Main Lobby Elevator</span>
                            <span class="facility-count">6 reports</span>
                        </div>
                    </div>

                    <div class="facility-item">
                        <div class="facility-rank">02</div>
                        <div class="facility-details">
                            <span class="facility-title">North Wing Bathrooms</span>
                            <span class="facility-count">5 reports</span>
                        </div>
                    </div>

                    <div class="facility-item">
                        <div class="facility-rank">03</div>
                        <div class="facility-details">
                            <span class="facility-title">Server Room AC Unit B</span>
                            <span class="facility-count">4 reports</span>
                        </div>
                    </div>

                    <div class="facility-item">
                        <div class="facility-rank">04</div>
                        <div class="facility-details">
                            <span class="facility-title">Parking Garage Level 2</span>
                            <span class="facility-count">3 reports</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </section>
</div>
@endsection
