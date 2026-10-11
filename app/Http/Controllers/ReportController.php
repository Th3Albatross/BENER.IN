<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController
{
    public function create(Request $request)
    {
        $validated = $request->validate(
            [
                'title' => 'required|string|max:255',
                'category_id' => 'required|integer|exists:categories,id',
                'urgency' => 'required|in:low,medium,high,critical',
                'location' => 'required|string|max:150',
                'occurred_at' => 'nullable|date|before_or_equal:today',
                'description' => 'required|string|max:3000',
                'photos' => ['nullable', 'array', 'max:3'],
                'photos.*' => [
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:5120',
                ],
            ],
            [
                'title.required' => 'Judul laporan wajib diisi.',
                'title.max' => 'Judul laporan maksimal 255 karakter.',
                'category_id.required' => 'Kategori fasilitas wajib dipilih.',
                'category_id.exists' => 'Kategori fasilitas yang dipilih tidak valid.',
                'urgency.required' => 'Tingkat urgensi wajib dipilih.',
                'urgency.in' => 'Tingkat urgensi yang dipilih tidak valid.',
                'location.required' => 'Lokasi atau ruangan wajib diisi.',
                'occurred_at.date' => 'Format tanggal kejadian tidak valid.',
                'occurred_at.before_or_equal' => 'Tanggal kejadian tidak boleh melebihi hari ini.',
                'description.required' => 'Deskripsi masalah wajib diisi.',
                'description.max' => 'Deskripsi maksimal 3000 karakter.',
                'photos.array' => 'Format daftar foto tidak valid.',
                'photos.max' => 'Maksimal 3 foto untuk setiap laporan.',
                'photos.*.image' => 'Setiap file harus berupa gambar.',
                'photos.*.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
                'photos.*.max' => 'Ukuran setiap foto maksimal 5 MB.',            ]
        );


    }

    public function show(int $id) {}
}
