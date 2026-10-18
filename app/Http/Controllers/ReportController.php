<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Services\ScramblrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReportController
{
    public function __construct(
        protected ScramblrService $scramblrService
    ) {}

    public function show()
    {
        return view('report.create', [
            'categories' => Category::all(),
            'facilities' => Facility::all(),
            'identifier' => (string) Str::uuid7(),
        ]);
    }

    public function createReport(Request $request)
    {
        $validated = $request->validate([
            'identifier' => ['required', 'uuid:7', 'unique:reports,identifier'],
            'title' => ['required', 'string', 'max:255'],
            'categories_id' => ['nullable', 'integer', 'exists:categories,id'],
            'severity' => ['required', 'in:Low,Medium,High,Critical'],
            'facilities_id' => ['required', 'integer', 'exists:facilities,id'],
            'location_description' => ['required', 'string', 'max:255'],
            'incident_date' => ['nullable', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:3000'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $photos = $request->file('photos', []);
        unset($validated['photos']);

        $validated['incident_date'] ??= now()->toDateString();
        $validated['users_id'] = $request->user()->id;
        $report = Report::create($validated);

        foreach ($photos as $photo) {
            $photo->storeAs(
                'reports/' . $report->id,
                Str::random(10) . '.' . $photo->extension(),
                'local'
            );
        }

        return redirect('/dashboard')->with('success', 'Laporan berhasil dikirim.');
    }

    public function showReport(Request $request, string $uuid)
    {
        $report = Report::where('reports.users_id', $request->user()->id)
            ->where('reports.identifier', $uuid)
            ->join('users', 'reports.users_id', '=', 'users.id')
            ->join('facilities', 'reports.facilities_id', '=', 'facilities.id')
            ->leftJoin('categories', 'reports.categories_id', '=', 'categories.id')
            ->select(
                'reports.*',
                'users.name as user_name',
                'facilities.name as facilities_name',
                'categories.name as categories_name'
            )
            ->firstOrFail();

        $photos = Cache::remember('photos' . $report->identifier, $this->scramblrService->expiration, function () use ($report) {
            $directory = 'reports/' . $report->id;
            $files = Storage::disk('local')->exists($directory)
                ? Storage::disk('local')->allFiles($directory)
                : [];

            return array_map(fn ($file) => $this->scramblrService->Scramble($file), $files);
        });

        return view('report.show', [
            '_report' => $report,
            '_photos' => $photos,
        ]);
    }

    public function editReport(Request $request, string $uuid)
    {
        $report = Report::where('users_id', $request->user()->id)
            ->where('identifier', $uuid)
            ->firstOrFail();

        abort_unless($report->status === 'Pending', 403);

        $photos = Cache::remember('photos' . $report->identifier, $this->scramblrService->expiration, function () use ($report) {
            $directory = 'reports/' . $report->id;
            $files = Storage::disk('local')->exists($directory)
                ? Storage::disk('local')->allFiles($directory)
                : [];

            return array_map(fn ($file) => $this->scramblrService->Scramble($file), $files);
        });

        return view('report.edit', [
            'report' => $report,
            'categories' => Category::all(),
            'facilities' => Facility::all(),
            'existingPhotos' => $photos,
        ]);
    }

    public function updateReport(Request $request, string $uuid)
    {
        $report = Report::where('users_id', $request->user()->id)
            ->where('identifier', $uuid)
            ->firstOrFail();

        abort_unless($report->status === 'Pending', 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'categories_id' => ['nullable', 'integer', 'exists:categories,id'],
            'severity' => ['required', 'in:Low,Medium,High,Critical'],
            'facilities_id' => ['required', 'integer', 'exists:facilities,id'],
            'location_description' => ['required', 'string', 'max:255'],
            'incident_date' => ['nullable', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string', 'max:3000'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_photos' => ['nullable', 'array', 'max:3'],
            'remove_photos.*' => ['required', 'string', 'max:4096'],
        ]);

        $newPhotos = $request->file('photos', []);
        $removeTokens = $validated['remove_photos'] ?? [];
        unset($validated['photos'], $validated['remove_photos']);

        $disk = Storage::disk('local');
        $directory = 'reports/' . $report->id;
        $currentPaths = $disk->exists($directory) ? $disk->allFiles($directory) : [];
        $removePaths = [];

        foreach ($removeTokens as $token) {
            try {
                [$valid, $path] = $this->scramblrService->Descramble($token);
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    'remove_photos' => 'Token foto tidak valid. Muat ulang halaman lalu coba lagi.',
                ]);
            }

            if (!$valid || !Str::startsWith($path, $directory . '/') || !in_array($path, $currentPaths, true)) {
                throw ValidationException::withMessages([
                    'remove_photos' => 'Foto yang dipilih tidak valid. Muat ulang halaman lalu coba lagi.',
                ]);
            }

            $removePaths[] = $path;
        }

        $removePaths = array_values(array_unique($removePaths));
        $remainingPaths = array_diff($currentPaths, $removePaths);

        if (count($remainingPaths) + count($newPhotos) > 3) {
            throw ValidationException::withMessages([
                'photos' => 'Jumlah foto setelah perubahan maksimal 3.',
            ]);
        }

        $validated['incident_date'] ??= now()->toDateString();
        $report->fill($validated);
        $report->edit_count++;
        $report->save();

        foreach ($newPhotos as $photo) {
            $photo->storeAs(
                $directory,
                Str::random(10) . '.' . $photo->extension(),
                'local'
            );
        }

        if ($removePaths) {
            $disk->delete($removePaths);
        }

        Cache::forget('photos' . $report->identifier);

        return redirect()
            ->route('reports.show', ['uuid' => $report->identifier])
            ->with('success', 'Perubahan laporan berhasil disimpan.');
    }

    public function createComment()
    {
    }
}
