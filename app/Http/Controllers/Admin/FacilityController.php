<?php

namespace App\Http\Controllers\Admin;

use App\Models\Facility;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FacilityController
{
    public function index()
    {
        $facilities = Facility::query()
            ->withCount('reports')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.facilities.index', [
            'facilities' => $facilities,
        ]);
    }

    public function create()
    {
        return view('admin.facilities.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], $this->messages());

        Facility::create($validated);

        return redirect()
            ->route('admin.facilities.index')
            ->with('status', 'facility-created');
    }

    public function edit(Facility $facility)
    {
        return view('admin.facilities.edit', [
            'facility' => $facility,
        ]);
    }

    public function update(Request $request, Facility $facility)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ], $this->messages());

        $facility->update($validated);

        return redirect()
            ->route('admin.facilities.index')
            ->with('status', 'facility-updated');
    }

    public function destroy(Facility $facility)
    {
        $reportCount = Report::query()->where('facilities_id', $facility->getKey())->count();

        // reports.facilities_id is a non-null foreign key: deleting a facility
        // that is still referenced would break those reports.
        if ($reportCount > 0) {
            throw ValidationException::withMessages([
                'facility' => 'Fasilitas ini masih digunakan oleh '.$reportCount.' laporan sehingga tidak dapat dihapus.',
            ]);
        }

        $facility->delete();

        return redirect()
            ->route('admin.facilities.index')
            ->with('status', 'facility-deleted');
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => 'Masukkan nama fasilitas.',
            'name.max' => 'Nama fasilitas maksimal 255 karakter.',
        ];
    }
}
