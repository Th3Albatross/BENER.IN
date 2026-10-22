<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use App\Services\ScramblrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResponsesController
{
    private const STAFF_ROLES = ['petugas', 'attendant', 'staff', 'admin'];

    private const VALID_STATUSES = ['all', 'Pending', 'Resolved', 'Rejected'];

    private const PROFILE_PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        protected ScramblrService $scramblrService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $isStaff = $this->isStaff($user);
        $statusFilter = $request->query('status', 'all');

        if (! in_array($statusFilter, self::VALID_STATUSES, true)) {
            $statusFilter = 'all';
        }

        $statusCountsQuery = Report::query();
        $reportsQuery = Report::query()
            ->with([
                'user:id,name',
                'facility:id,name',
                'category:id,name',
            ])
            ->withCount('responses')
            ->withMax('responses', 'created_at');

        if (! $isStaff) {
            $statusCountsQuery->where('users_id', $user->id);
            $reportsQuery->where('users_id', $user->id);
        }

        $statusCounts = $statusCountsQuery
            ->select('status')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('status')
            ->pluck('total', 'status');

        if ($statusFilter !== 'all') {
            $reportsQuery->where('status', $statusFilter);
        }

        $reports = $reportsQuery
            // Keep the existing behavior: order by the latest response, or by
            // the report's update time when the discussion has no responses.
            ->orderByRaw(
                'COALESCE((SELECT MAX(responses.created_at) FROM responses WHERE responses.reports_id = reports.id), reports.updated_at) DESC'
            )
            ->orderByDesc('reports.id')
            ->paginate(10)
            ->withQueryString();

        $reports->getCollection()->each(function (Report $report): void {
            $this->attachProfilePhotoUrl($report->user);
        });

        return view('discussion.index', [
            '_reports' => $reports,
            '_statusFilter' => $statusFilter,
            '_statusCounts' => $statusCounts,
        ]);
    }

    public function show(Request $request, string $uuid)
    {
        $report = Report::query()
            ->with([
                'user:id,name',
                'facility:id,name',
                'category:id,name',
            ])
            ->where('identifier', $uuid)
            ->firstOrFail();

        $user = $request->user();
        abort_unless(
            (int) $report->users_id === (int) $user->id || $this->isStaff($user),
            403
        );

        $this->attachProfilePhotoUrl($report->user);
        $this->attachProfilePhotoUrl($user);

        $responses = $report->responses()
            ->with('user:id,name,role')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $responses->each(function ($response): void {
            $this->attachProfilePhotoUrl($response->user);
        });

        return view('discussion.show', [
            '_report' => $report,
            '_responses' => $responses,
            '_currentUser' => $user,
        ]);
    }

    public function store(Request $request, string $uuid)
    {
        $report = Report::query()
            ->where('identifier', $uuid)
            ->firstOrFail();

        $user = $request->user();
        abort_unless(
            (int) $report->users_id === (int) $user->id || $this->isStaff($user),
            403
        );
        abort_unless($report->status === 'Pending', 403);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $report->responses()->create([
            'users_id' => $user->id,
            'message' => $validated['message'],
        ]);

        return redirect()
            ->route('responses.show', $this->returnParams($request, $report))
            ->with('success', 'Pesan berhasil dikirim.');
    }

    public function update(Request $request, string $uuid, int $responseId)
    {
        $report = Report::query()
            ->where('identifier', $uuid)
            ->firstOrFail();

        $user = $request->user();
        abort_unless(
            (int) $report->users_id === (int) $user->id || $this->isStaff($user),
            403
        );
        abort_unless($report->status === 'Pending', 403);

        // A user may edit only their own response, and only within this report.
        $response = $report->responses()
            ->whereKey($responseId)
            ->where('users_id', $user->id)
            ->firstOrFail();

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $response->update([
            'message' => $validated['message'],
        ]);

        return redirect()
            ->route('responses.show', $this->returnParams($request, $report))
            ->with('success', 'Pesan berhasil diperbarui.');
    }

    /** Attach a temporary URL served through the existing private blob route. */
    private function attachProfilePhotoUrl(?User $user): void
    {
        if (! $user) {
            return;
        }

        $path = $this->findProfilePhotoPath($user);
        $user->setAttribute(
            'profile_photo_url',
            $path
                ? route('blob.serve', [
                    'scrambldToken' => $this->scramblrService->Scramble($path),
                ])
                : null
        );
    }

    private function findProfilePhotoPath(User $user): ?string
    {
        $disk = Storage::disk('local');
        $directory = 'profile-photos/'.$user->getKey();

        foreach (self::PROFILE_PHOTO_EXTENSIONS as $extension) {
            $path = $directory.'/avatar.'.$extension;
            if ($disk->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function isStaff(User $user): bool
    {
        return in_array(mb_strtolower((string) $user->role), self::STAFF_ROLES, true);
    }

    private function returnParams(Request $request, Report $report): array
    {
        $params = ['uuid' => $report->identifier];

        if ($request->input('from') !== 'discussions') {
            return $params;
        }

        $status = (string) $request->input('status', 'all');
        $params['from'] = 'discussions';
        $params['status'] = in_array($status, self::VALID_STATUSES, true) ? $status : 'all';

        $page = $request->integer('page');
        if ($page > 0) {
            $params['page'] = $page;
        }

        return $params;
    }
}
