<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Facility;
use App\Models\Report;
use App\Services\ImageLoaderService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ReportController
{

    public function __construct(
        protected ImageLoaderService $imageLoaderService
    ) {}
    public function create(Request $request)
    {
        $validated = $request->validate([
            'identifier' => [
                'required',
                'uuid:7',
                'unique:reports,identifier'
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'categories_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'severity' => [
                'required',
                'in:Low,Medium,High,Critical',
            ],
            'facilities_id' => [
                'required',
                'integer',
                'exists:facilities,id',
            ],
            'location_description' => [
                'required',
                'string',
                'max:255',
            ],
            'incident_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'description' => [
                'required',
                'string',
                'max:3000',
            ],
            'photos' => [
                'nullable',
                'array',
                'max:3',
            ],
            'photos.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ], [
            // Identifier
            'identifier.required' => 'Token identifikasi laporan wajib disertakan.',
            'identifier.uuid' => 'Token identifikasi laporan tidak valid.',
            'identifier.unique' => 'Token sudah digunakan untuk membuat laporan.',

            // Title
            'title.required' => 'Judul laporan wajib diisi.',
            'title.string' => 'Judul laporan harus berupa teks.',
            'title.max' => 'Judul laporan maksimal 255 karakter.',

            // Category
            'categories_id.integer' => 'Kategori laporan tidak valid.',
            'categories_id.exists' => 'Kategori laporan tidak ditemukan.',

            // Severity
            'severity.required' => 'Tingkat urgensi wajib dipilih.',
            'severity.in' => 'Tingkat urgensi tidak valid.',

            // Facility
            'facilities_id.required' => 'Fasilitas wajib dipilih.',
            'facilities_id.integer' => 'Fasilitas yang dipilih tidak valid.',
            'facilities_id.exists' => 'Fasilitas yang dipilih tidak ditemukan.',

            // Location
            'location_description.required' => 'Detail lokasi wajib diisi.',
            'location_description.string' => 'Detail lokasi harus berupa teks.',
            'location_description.max' => 'Detail lokasi maksimal 255 karakter.',

            // Incident date
            'incident_date.date' => 'Tanggal kejadian tidak valid.',
            'incident_date.before_or_equal' => 'Tanggal kejadian tidak boleh melebihi hari ini.',

            // Description
            'description.required' => 'Deskripsi laporan wajib diisi.',
            'description.string' => 'Deskripsi laporan harus berupa teks.',
            'description.max' => 'Deskripsi laporan maksimal 3000 karakter.',

            // Photos
            'photos.array' => 'Format data foto tidak valid.',
            'photos.max' => 'Maksimal 3 foto untuk setiap laporan.',
            'photos.*.required' => 'Setiap file foto wajib diisi.',
            'photos.*.image' => 'Setiap file harus berupa gambar.',
            'photos.*.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
            'photos.*.max' => 'Ukuran setiap foto maksimal 5 MB.',
        ]);

        $validated['incident_date'] ??= now()->toDateString();
        $validated['users_id'] = $request->user()->id;

        $report = Report::create($validated);
        foreach ($request->files->all('photos') as $idx => $_) {
            $request->file('photos')[$idx]->store('reports/'.$report->id);
        }

        return redirect()->to('dashboard');
    }

    public function showReport(Request $request, string $uuid) {
        $report = Report::where('users_id', $request->user()->id)
                    ->where('identifier', $uuid)
                    ->join('facilities', 'reports.facilities_id', '=', 'facilities.id')
                    ->leftJoin('categories', 'reports.categories_id', '=', 'categories.id')
                    ->select('reports.*', 'facilities.name as facilities_name', 'categories.name as categories_name')
                    ->firstOrFail();

        $photos = [];

        $this->imageLoaderService->loadImages($report);
        return view('report.show', [
            '_report' => $report,
            '_photos' => $photos
        ]);
    }

    public function show()
    {
        return view('report.create', [
            'categories' => Category::all(),
            'facilities' => Facility::all(),
            'identifier' => (string) Str::uuid7(),
        ]);
    }

    public function serveImage(string $uuid, string $filename) {
        $path = 'temporary/'.$uuid.'/'.$filename;
        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        return response()->file($disk->path($path));
    }
}
