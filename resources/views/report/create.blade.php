@extends('layouts.app')

@push('styles')
    @vite('resources/css/report-create.css')
@endpush

@section('title', 'Buat Laporan')
@section('page-title', 'Buat Laporan')
@section('page-description', 'Laporkan kerusakan atau masalah fasilitas sekolah.')

@section('content')
@php
    // Mendukung nama variabel controller dengan atau tanpa awalan underscore.
    $reportCategories = $categories ?? ($_categories ?? collect());
    $reportFacilities = $facilities ?? ($_facilities ?? collect());
@endphp

<div class="report-create-widget">
    @if (session('success'))
        <div class="report-alert report-alert--success" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="m5 12 4 4L19 6" />
                <circle cx="12" cy="12" r="9" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="report-alert report-alert--error" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
                <path d="M12 8v4m0 4h.01" />
            </svg>
            <div>
                <strong>Laporan belum dapat dikirim.</strong>
                <span>Periksa kembali input yang ditandai. File foto perlu dipilih ulang jika validasi server gagal.</span>
            </div>
        </div>
    @endif

    <form
        class="report-create-form"
        id="facilityReportForm"
        action="{{ url('/reports') }}"
        method="POST"
        enctype="multipart/form-data"
    >
        @csrf

        <div class="report-create-grid">
            <section class="report-card report-details-card" aria-labelledby="reportDetailsTitle">
                <header class="report-card-header">
                    <span class="report-card-header-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <path d="M14 2v6h6M8 13h8M8 17h8M8 9h1" />
                        </svg>
                    </span>
                    <div>
                        <h2 id="reportDetailsTitle">Detail Laporan Fasilitas</h2>
                        <p>Lengkapi informasi agar petugas dapat memahami masalahnya.</p>
                    </div>
                </header>

                <div class="report-card-body">
                    <div class="report-field">
                        <label for="reportTitle">Judul Laporan <span class="report-required">*</span></label>
                        <input
                            id="reportTitle"
                            name="title"
                            type="text"
                            value="{{ old('title') }}"
                            placeholder="Contoh: Lampu ruang kelas tidak menyala"
                            maxlength="255"
                            autocomplete="off"
                            required
                            @error('title') aria-invalid="true" @enderror
                        >
                        @error('title')
                            <small class="report-field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="report-field-row">
                        <div class="report-field">
                            <label for="reportCategory">Kategori Fasilitas <span class="report-required">*</span></label>
                            <select id="reportCategory" name="categories_id" required @error('categories_id') aria-invalid="true" @enderror>
                                <option value="">Pilih kategori</option>
                                @forelse ($reportCategories as $category)
                                    <option
                                        value="{{ data_get($category, 'id') }}"
                                        @selected((string) old('categories_id') === (string) data_get($category, 'id'))
                                    >{{ data_get($category, 'name', data_get($category, 'categories_name', 'Kategori')) }}</option>
                                @empty
                                    {{-- Opsi akan terisi saat koleksi kategori diberikan oleh controller. --}}
                                @endforelse
                            </select>
                            @error('categories_id')
                                <small class="report-field-error">{{ $message }}</small>
                            @enderror
                            @if (count($reportCategories) === 0)
                                <small class="report-field-hint">Data kategori belum tersedia di halaman ini.</small>
                            @endif
                        </div>

                        <div class="report-field">
                            <label for="reportSeverity">Tingkat Urgensi <span class="report-required">*</span></label>
                            <select id="reportSeverity" name="severity" required @error('severity') aria-invalid="true" @enderror>
                                <option value="">Pilih urgensi</option>
                                <option value="Low" @selected(old('severity') === 'Low')>Rendah — tidak mengganggu aktivitas</option>
                                <option value="Medium" @selected(old('severity') === 'Medium')>Sedang — sedikit mengganggu</option>
                                <option value="High" @selected(old('severity') === 'High')>Tinggi — mengganggu kegiatan belajar</option>
                                <option value="Critical" @selected(old('severity') === 'Critical')>Kritis — berpotensi membahayakan</option>
                            </select>
                            @error('severity')
                                <small class="report-field-error">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="report-field">
                        <label for="reportFacility">Fasilitas / Lokasi <span class="report-required">*</span></label>
                        <select id="reportFacility" name="facilities_id" required @error('facilities_id') aria-invalid="true" @enderror>
                            <option value="">Pilih fasilitas yang bermasalah</option>
                            @forelse ($reportFacilities as $facility)
                                <option
                                    value="{{ data_get($facility, 'id') }}"
                                    @selected((string) old('facilities_id') === (string) data_get($facility, 'id'))
                                >{{ data_get($facility, 'name', data_get($facility, 'facilities_name', 'Fasilitas')) }}</option>
                            @empty
                                {{-- Opsi akan terisi saat koleksi fasilitas diberikan oleh controller. --}}
                            @endforelse
                        </select>
                        @error('facilities_id')
                            <small class="report-field-error">{{ $message }}</small>
                        @enderror
                        @if (count($reportFacilities) === 0)
                            <small class="report-field-hint">Data fasilitas belum tersedia di halaman ini.</small>
                        @endif
                    </div>

                    <div class="report-field">
                        <label for="reportDescription">Deskripsi Masalah <span class="report-required">*</span></label>
                        <textarea
                            id="reportDescription"
                            name="description"
                            rows="5"
                            maxlength="3000"
                            placeholder="Jelaskan kondisi kerusakan, sejak kapan terjadi, dan dampaknya terhadap kegiatan belajar..."
                            required
                            @error('description') aria-invalid="true" @enderror
                        >{{ old('description') }}</textarea>
                        <div class="report-field-footnote">
                            <span>Berikan detail yang jelas agar petugas lebih mudah menindaklanjuti.</span>
                            <span id="reportDescriptionCounter">{{ strlen(old('description', '')) }}/3000</span>
                        </div>
                        @error('description')
                            <small class="report-field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="report-field report-photo-field">
                        <div class="report-photo-heading">
                            <label for="reportPhotos">Foto Bukti <span class="report-optional">(opsional)</span></label>
                            <span class="report-photo-limit">0/3 foto</span>
                        </div>
                        <input
                            class="report-file-input"
                            id="reportPhotos"
                            name="photos[]"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            aria-describedby="reportPhotoHelp reportPhotoError"
                            @if ($errors->has('photos') || $errors->has('photos.0')) aria-invalid="true" @endif
                        >
                        <label class="report-upload-area" for="reportPhotos" id="reportUploadArea">
                            <span class="report-upload-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                    <path d="M12 16V4m-4 4 4-4 4 4" />
                                    <path d="M4 16.5v2A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5v-2" />
                                </svg>
                            </span>
                            <span class="report-upload-title"><strong>Pilih foto</strong> atau seret ke sini</span>
                            <span class="report-upload-help" id="reportPhotoHelp">JPG, PNG, atau WEBP · Maksimal 3 foto · 5 MB per foto</span>
                        </label>
                        <p class="report-photo-message" id="reportPhotoMessage" role="status" aria-live="polite"></p>
                        <div class="report-photo-list" id="reportPhotoList" aria-label="Daftar foto terpilih"></div>
                        @if ($errors->has('photos') || $errors->has('photos.0') || $errors->has('photos.1') || $errors->has('photos.2'))
                            <small class="report-field-error" id="reportPhotoError">
                                {{ $errors->first('photos') ?: $errors->first('photos.0') ?: $errors->first('photos.1') ?: $errors->first('photos.2') }}
                            </small>
                        @endif
                    </div>
                </div>
            </section>

            <aside class="report-create-aside" aria-label="Aksi dan panduan laporan">
                <section class="report-card report-submit-card" aria-label="Aksi laporan">
                    <div class="report-card-body">
                        <button type="submit" class="report-submit-button">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="m22 2-7 20-4-9-9-4Z" />
                                <path d="M22 2 11 13" />
                            </svg>
                            Kirim Laporan
                        </button>
                        <button type="reset" class="report-reset-button" id="resetReportForm">Batal &amp; Reset</button>
                        <p class="report-submit-note">Pilih fasilitas yang sesuai dan lampirkan foto jika tersedia. Kamu dapat memantau status laporan setelah dikirim.</p>
                    </div>
                </section>

                <section class="report-card report-tips-card" aria-labelledby="reportTipsTitle">
                    <header class="report-tips-header">
                        <span class="report-tips-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M9 18h6m-5 4h4m-5-8.5a6 6 0 1 1 6 0c-.9.7-1 1.5-1 2.5h-4c0-1-.1-1.8-1-2.5Z" />
                            </svg>
                        </span>
                        <div>
                            <h2 id="reportTipsTitle">Tips Laporan yang Baik</h2>
                            <p>Agar masalah lebih mudah ditangani</p>
                        </div>
                    </header>
                    <div class="report-tips-body">
                        <div class="report-tip-item">
                            <span class="report-tip-number">1</span>
                            <div class="report-tip-copy">
                                <strong>Gunakan judul yang jelas</strong>
                                <p>Sebutkan nama fasilitas dan masalah yang terjadi.</p>
                            </div>
                        </div>
                        <div class="report-tip-item">
                            <span class="report-tip-number">2</span>
                            <div class="report-tip-copy">
                                <strong>Jelaskan masalah secara detail</strong>
                                <p>Ceritakan kondisi kerusakan dan dampaknya.</p>
                            </div>
                        </div>
                        <div class="report-tip-item">
                            <span class="report-tip-number">3</span>
                            <div class="report-tip-copy">
                                <strong>Pilih urgensi dengan tepat</strong>
                                <p>Gunakan tingkat kritis jika ada risiko keselamatan atau perlu penanganan segera.</p>
                            </div>
                        </div>
                        <div class="report-tip-item">
                            <span class="report-tip-number">4</span>
                            <div class="report-tip-copy">
                                <strong>Sertakan foto bila memungkinkan</strong>
                                <p>Maksimal tiga foto dapat membantu petugas memahami kondisi fasilitas.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </aside>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('facilityReportForm');
        if (!form) return;

        const photoInput = document.getElementById('reportPhotos');
        const uploadArea = document.getElementById('reportUploadArea');
        const photoList = document.getElementById('reportPhotoList');
        const photoLimit = document.querySelector('.report-photo-limit');
        const photoMessage = document.getElementById('reportPhotoMessage');
        const description = document.getElementById('reportDescription');
        const descriptionCounter = document.getElementById('reportDescriptionCounter');
        const maxPhotos = 3;
        const maxFileSize = 5 * 1024 * 1024;
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        let selectedPhotos = [];
        const previewUrls = new Map();

        function keyFor(file) {
            return [file.name, file.size, file.lastModified, file.type].join('::');
        }

        function getPreviewUrl(file) {
            const key = keyFor(file);
            if (!previewUrls.has(key)) {
                previewUrls.set(key, URL.createObjectURL(file));
            }
            return previewUrls.get(key);
        }

        function revokePreviewUrl(file) {
            const key = keyFor(file);
            if (previewUrls.has(key)) {
                URL.revokeObjectURL(previewUrls.get(key));
                previewUrls.delete(key);
            }
        }

        function syncInputFiles() {
            try {
                const transfer = new DataTransfer();
                selectedPhotos.forEach(function (file) {
                    transfer.items.add(file);
                });
                photoInput.files = transfer.files;
                return true;
            } catch (error) {
                photoMessage.textContent = 'Browser ini tidak mendukung pengelolaan beberapa foto. Coba gunakan browser versi terbaru.';
                return false;
            }
        }

        function formatSize(bytes) {
            return bytes >= 1024 * 1024
                ? (bytes / (1024 * 1024)).toFixed(2) + ' MB'
                : Math.max(1, Math.round(bytes / 1024)) + ' KB';
        }

        function renderPhotos() {
            photoList.replaceChildren();
            selectedPhotos.forEach(function (file, index) {
                const item = document.createElement('div');
                item.className = 'report-photo-item';

                const image = document.createElement('img');
                image.className = 'report-photo-preview';
                image.src = getPreviewUrl(file);
                image.alt = 'Pratinjau foto ' + (index + 1);

                const details = document.createElement('div');
                details.className = 'report-photo-details';

                const name = document.createElement('span');
                name.className = 'report-photo-name';
                name.textContent = file.name;

                const size = document.createElement('span');
                size.className = 'report-photo-size';
                size.textContent = formatSize(file.size);

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'report-remove-photo';
                remove.dataset.removeIndex = String(index);
                remove.textContent = 'Hapus';
                remove.setAttribute('aria-label', 'Hapus ' + file.name);

                details.append(name, size);
                item.append(image, details, remove);
                photoList.appendChild(item);
            });

            photoLimit.textContent = selectedPhotos.length + '/' + maxPhotos + ' foto';
            uploadArea.classList.toggle('report-upload-area--has-file', selectedPhotos.length > 0);
            uploadArea.classList.toggle('report-upload-area--limit-reached', selectedPhotos.length >= maxPhotos);
        }

        function addPhotos(fileList) {
            const incoming = Array.from(fileList || []);
            const messages = [];
            let added = 0;

            incoming.forEach(function (file) {
                if (!allowedTypes.includes(file.type)) {
                    messages.push(file.name + ': format tidak didukung. Gunakan JPG, PNG, atau WEBP.');
                    return;
                }

                if (file.size > maxFileSize) {
                    messages.push(file.name + ': ukuran lebih dari 5 MB.');
                    return;
                }

                if (selectedPhotos.some(function (existing) { return keyFor(existing) === keyFor(file); })) {
                    return;
                }

                if (selectedPhotos.length >= maxPhotos) {
                    messages.push('Maksimal ' + maxPhotos + ' foto per laporan.');
                    return;
                }

                selectedPhotos.push(file);
                added++;
            });

            syncInputFiles();
            renderPhotos();
            photoMessage.textContent = messages.join(' ');

            if (messages.length) {
                window.alert(messages.join('\n'));
            } else if (added > 0) {
                photoMessage.textContent = added + ' foto berhasil dipilih.';
            }
        }

        photoInput.addEventListener('change', function () {
            // Tambahkan foto baru ke pilihan yang sudah ada; tetap batasi maksimum tiga.
            addPhotos(photoInput.files);
        });

        photoList.addEventListener('click', function (event) {
            const button = event.target.closest('[data-remove-index]');
            if (!button) return;

            const index = Number(button.dataset.removeIndex);
            const removed = selectedPhotos[index];
            if (!removed) return;

            selectedPhotos.splice(index, 1);
            revokePreviewUrl(removed);
            syncInputFiles();
            renderPhotos();
            photoMessage.textContent = 'Foto berhasil dihapus.';
        });

        uploadArea.addEventListener('dragover', function (event) {
            event.preventDefault();
            uploadArea.classList.add('report-upload-area--dragover');
        });

        uploadArea.addEventListener('dragleave', function () {
            uploadArea.classList.remove('report-upload-area--dragover');
        });

        uploadArea.addEventListener('drop', function (event) {
            event.preventDefault();
            uploadArea.classList.remove('report-upload-area--dragover');
            addPhotos(event.dataTransfer ? event.dataTransfer.files : []);
        });

        description.addEventListener('input', function () {
            descriptionCounter.textContent = description.value.length + '/3000';
        });

        form.addEventListener('reset', function () {
            window.setTimeout(function () {
                selectedPhotos.forEach(revokePreviewUrl);
                selectedPhotos = [];
                previewUrls.forEach(function (url) { URL.revokeObjectURL(url); });
                previewUrls.clear();
                photoInput.value = '';
                photoMessage.textContent = '';
                renderPhotos();
                descriptionCounter.textContent = description.value.length + '/3000';
            }, 0);
        });

        renderPhotos();
        descriptionCounter.textContent = description.value.length + '/3000';
    });
</script>
@endpush
