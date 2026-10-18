{{-- Shared report form. Dipakai oleh halaman create dan edit. --}}
@php
    $isEdit = $isEdit ?? false;
    if (!$isEdit) {
        $reportRecord = null;
    } else {
        $reportRecord = $reportRecord ?? ($report ?? ($_report ?? null));
    }
    $reportCategories = $categories ?? ($_categories ?? collect());
    $reportFacilities = $facilities ?? ($_facilities ?? collect());
    $existingPhotos = $existingPhotos ?? ($photos ?? ($_photos ?? []));
    $existingPhotos = is_array($existingPhotos) ? array_values($existingPhotos) : [];
    $incidentDate = old('incident_date', data_get($reportRecord, 'incident_date'));
    try {
        $incidentDate = $incidentDate
            ? \Illuminate\Support\Carbon::parse($incidentDate)->toDateString()
            : now()->toDateString();
    } catch (\Throwable $exception) {
        $incidentDate = now()->toDateString();
    }
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
                <strong>{{ $isEdit ? 'Perubahan belum dapat disimpan.' : 'Laporan belum dapat dikirim.' }}</strong>
                <span>Periksa kembali input yang ditandai. File foto baru perlu dipilih ulang jika validasi server gagal.</span>
            </div>
        </div>
    @endif

    <form
        class="report-create-form"
        id="facilityReportForm"
        action="{{ $formAction ?? ($isEdit ? url('/reports/' . data_get($reportRecord, 'identifier')) : url('/reports')) }}"
        method="POST"
        enctype="multipart/form-data"
    >
        @csrf
        @if ($isEdit)
            @method('PUT')
        @else
            <input type="hidden" name="identifier" value="{{ old('identifier', $identifier ?? '') }}">
        @endif

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
                        <h2 id="reportDetailsTitle">{{ $isEdit ? 'Edit Detail Laporan' : 'Detail Laporan Fasilitas' }}</h2>
                        <p>{{ $isEdit ? 'Perbarui informasi laporan sebelum menyimpan perubahan.' : 'Lengkapi informasi agar petugas dapat memahami masalahnya.' }}</p>
                    </div>
                </header>

                <div class="report-card-body">
                    <div class="report-field">
                        <label for="reportTitle">Judul Laporan <span class="report-required">*</span></label>
                        <input
                            id="reportTitle"
                            name="title"
                            type="text"
                            value="{{ old('title', data_get($reportRecord, 'title', '')) }}"
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
                            <label for="reportCategory">Kategori Laporan <span class="report-optional">(opsional)</span></label>
                            <select id="reportCategory" name="categories_id" @error('categories_id') aria-invalid="true" @enderror>
                                <option value="">Tanpa kategori</option>
                                @forelse ($reportCategories as $category)
                                    <option
                                        value="{{ data_get($category, 'id') }}"
                                        @selected((string) old('categories_id', data_get($reportRecord, 'categories_id')) === (string) data_get($category, 'id'))
                                    >{{ data_get($category, 'name', data_get($category, 'categories_name', data_get($category, 'category_name', 'Kategori'))) }}</option>
                                @empty
                                    {{-- Opsi akan terisi saat koleksi kategori diberikan oleh controller. --}}
                                @endforelse
                            </select>
                            @error('categories_id')
                                <small class="report-field-error">{{ $message }}</small>
                            @enderror
                            @if (count($reportCategories) === 0)
                                <small class="report-field-hint">Data kategori belum tersedia. Laporan tetap dapat dikirim tanpa kategori.</small>
                            @endif
                        </div>

                        <div class="report-field">
                            <label for="reportSeverity">Tingkat Urgensi <span class="report-required">*</span></label>
                            <select id="reportSeverity" name="severity" required @error('severity') aria-invalid="true" @enderror>
                                <option value="">Pilih urgensi</option>
                                <option value="Low" @selected(old('severity', data_get($reportRecord, 'severity')) === 'Low')>Rendah — tidak mengganggu aktivitas</option>
                                <option value="Medium" @selected(old('severity', data_get($reportRecord, 'severity')) === 'Medium')>Sedang — sedikit mengganggu</option>
                                <option value="High" @selected(old('severity', data_get($reportRecord, 'severity')) === 'High')>Tinggi — mengganggu kegiatan belajar</option>
                                <option value="Critical" @selected(old('severity', data_get($reportRecord, 'severity')) === 'Critical')>Kritis — berpotensi membahayakan</option>
                            </select>
                            @error('severity')
                                <small class="report-field-error">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="report-field">
                        <label for="reportFacility">Fasilitas yang Dilaporkan <span class="report-required">*</span></label>
                        <select id="reportFacility" name="facilities_id" required @error('facilities_id') aria-invalid="true" @enderror>
                            <option value="">Pilih fasilitas yang bermasalah</option>
                            @forelse ($reportFacilities as $facility)
                                <option
                                    value="{{ data_get($facility, 'id') }}"
                                    @selected((string) old('facilities_id', data_get($reportRecord, 'facilities_id')) === (string) data_get($facility, 'id'))
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

                    <div class="report-field-row">
                        <div class="report-field">
                            <label for="reportLocationDescription">Detail Lokasi <span class="report-required">*</span></label>
                            <input
                                id="reportLocationDescription"
                                name="location_description"
                                type="text"
                                value="{{ old('location_description', data_get($reportRecord, 'location_description', '')) }}"
                                maxlength="255"
                                placeholder="Contoh: Gedung A, lantai 2, dekat ruang 204"
                                autocomplete="off"
                                required
                                @error('location_description') aria-invalid="true" @enderror
                            >
                            @error('location_description')
                                <small class="report-field-error">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="report-field">
                            <label for="reportIncidentDate">Tanggal Kejadian <span class="report-optional">(opsional)</span></label>
                            <input
                                id="reportIncidentDate"
                                name="incident_date"
                                type="date"
                                value="{{ $incidentDate }}"
                                max="{{ now()->toDateString() }}"
                                @error('incident_date') aria-invalid="true" @enderror
                            >
                            <small class="report-field-hint">Jika dikosongkan, tanggal hari ini akan digunakan.</small>
                            @error('incident_date')
                                <small class="report-field-error">{{ $message }}</small>
                            @enderror
                        </div>
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
                        >{{ old('description', data_get($reportRecord, 'description', '')) }}</textarea>
                        <div class="report-field-footnote">
                            <span>Berikan detail yang jelas agar petugas lebih mudah menindaklanjuti.</span>
                            <span id="reportDescriptionCounter">{{ strlen(old('description', data_get($reportRecord, 'description', '')) ?? '') }}/3000</span>
                        </div>
                        @error('description')
                            <small class="report-field-error">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="report-field report-photo-field">
                        @if ($isEdit && count($existingPhotos) > 0)
                            <div class="report-existing-photos">
                                <p class="report-existing-photos-title">Foto yang sudah tersimpan</p>
                                <p class="report-field-hint">Foto lama tetap dipertahankan kecuali kamu menandainya untuk dihapus saat menyimpan.</p>
                                <div class="report-existing-photo-grid">
                                    @foreach ($existingPhotos as $photoIndex => $photoToken)
                                        <div class="report-existing-photo">
                                            <img
                                                src="{{ url('/blob/' . $photoToken) }}"
                                                alt="Foto bukti tersimpan {{ $photoIndex + 1 }}"
                                                loading="lazy"
                                            >
                                            <span class="report-existing-photo-caption">Foto {{ $photoIndex + 1 }}</span>
                                            <label class="report-existing-photo-remove" for="removePhoto{{ $photoIndex }}">
                                                <input
                                                    id="removePhoto{{ $photoIndex }}"
                                                    type="checkbox"
                                                    name="remove_photos[]"
                                                    value="{{ $photoToken }}"
                                                    data-remove-existing-photo
                                                >
                                                Hapus saat menyimpan
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @elseif ($isEdit)
                            <p class="report-field-hint">Belum ada foto bukti tersimpan untuk laporan ini.</p>
                        @endif

                        <div class="report-photo-heading">
                            <label for="reportPhotos">{{ $isEdit ? 'Tambah Foto Bukti' : 'Foto Bukti' }} <span class="report-optional">(opsional)</span></label>
                            <span class="report-photo-limit" data-existing-count="{{ count($existingPhotos) }}">{{ count($existingPhotos) }}/3 foto</span>
                        </div>
                        <input
                            class="report-file-input"
                            id="reportPhotos"
                            name="photos[]"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            aria-describedby="reportPhotoHelp"
                            @if ($errors->has('photos') || $errors->has('photos.0') || $errors->has('photos.1') || $errors->has('photos.2')) aria-invalid="true" @endif
                        >
                        <label class="report-upload-area" for="reportPhotos" id="reportUploadArea">
                            <span class="report-upload-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                    <path d="M12 16V4m-4 4 4-4 4 4" />
                                    <path d="M4 16.5v2A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5v-2" />
                                </svg>
                            </span>
                            <span class="report-upload-title"><strong>Pilih foto</strong> atau seret ke sini</span>
                            <span class="report-upload-help" id="reportPhotoHelp">JPG, PNG, atau WEBP · Maksimal 3 foto total · 5 MB per foto</span>
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
                            {{ $isEdit ? 'Simpan Perubahan' : 'Kirim Laporan' }}
                        </button>
                        <button type="reset" class="report-reset-button" id="resetReportForm">{{ $isEdit ? 'Reset Perubahan' : 'Batal & Reset' }}</button>
                        <p class="report-submit-note">{{ $isEdit ? 'Periksa kembali perubahan sebelum disimpan. Foto lama yang tidak ditandai untuk dihapus akan tetap tersimpan.' : 'Pilih fasilitas dan isi detail lokasi dengan jelas. Kategori laporan bersifat opsional; lampirkan foto jika tersedia.' }}</p>
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
        const existingPhotoInputs = Array.from(form.querySelectorAll('[data-remove-existing-photo]'));
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
            if (!previewUrls.has(key)) previewUrls.set(key, URL.createObjectURL(file));
            return previewUrls.get(key);
        }

        function revokePreviewUrl(file) {
            const key = keyFor(file);
            if (previewUrls.has(key)) {
                URL.revokeObjectURL(previewUrls.get(key));
                previewUrls.delete(key);
            }
        }

        function retainedExistingCount() {
            return existingPhotoInputs.filter(function (input) { return !input.checked; }).length;
        }

        function availableNewSlots() {
            return Math.max(0, maxPhotos - retainedExistingCount());
        }

        function syncInputFiles() {
            try {
                const transfer = new DataTransfer();
                selectedPhotos.forEach(function (file) { transfer.items.add(file); });
                photoInput.files = transfer.files;
                return true;
            } catch (error) {
                photoMessage.textContent = 'Browser ini tidak mendukung pengelolaan beberapa foto. Coba browser versi terbaru.';
                return false;
            }
        }

        function formatSize(bytes) {
            return bytes >= 1024 * 1024
                ? (bytes / (1024 * 1024)).toFixed(2) + ' MB'
                : Math.max(1, Math.round(bytes / 1024)) + ' KB';
        }

        function updatePhotoCounter() {
            if (photoLimit) photoLimit.textContent = (retainedExistingCount() + selectedPhotos.length) + '/' + maxPhotos + ' foto';
            existingPhotoInputs.forEach(function (input) {
                const card = input.closest('.report-existing-photo');
                if (card) card.classList.toggle('report-existing-photo--marked', input.checked);
            });
            uploadArea.classList.toggle('report-upload-area--has-file', retainedExistingCount() + selectedPhotos.length > 0);
            uploadArea.classList.toggle('report-upload-area--limit-reached', availableNewSlots() <= selectedPhotos.length);
        }

        function renderPhotos() {
            photoList.replaceChildren();
            selectedPhotos.forEach(function (file, index) {
                const item = document.createElement('div');
                item.className = 'report-photo-item';
                const image = document.createElement('img');
                image.className = 'report-photo-preview';
                image.src = getPreviewUrl(file);
                image.alt = 'Pratinjau foto baru ' + (index + 1);
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
            updatePhotoCounter();
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
                if (selectedPhotos.some(function (existing) { return keyFor(existing) === keyFor(file); })) return;
                if (selectedPhotos.length >= availableNewSlots()) {
                    messages.push('Maksimal ' + maxPhotos + ' foto total. Hapus/tandai foto lama untuk dihapus jika ingin menggantinya.');
                    return;
                }
                selectedPhotos.push(file);
                added++;
            });
            syncInputFiles();
            renderPhotos();
            photoMessage.textContent = messages.length ? messages.join(' ') : (added > 0 ? added + ' foto berhasil dipilih.' : '');
            if (messages.length) window.alert(messages.join('\n'));
        }

        photoInput.addEventListener('change', function () {
            const incoming = Array.from(photoInput.files || []);
            photoInput.value = '';
            addPhotos(incoming);
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
            photoMessage.textContent = 'Foto baru berhasil dihapus dari pilihan.';
        });

        existingPhotoInputs.forEach(function (input) {
            input.addEventListener('change', function () {
                // Batal menandai penghapusan jika itu membuat jumlah foto melebihi tiga.
                if (retainedExistingCount() + selectedPhotos.length > maxPhotos) {
                    input.checked = true;
                    photoMessage.textContent = 'Hapus salah satu foto baru terlebih dahulu agar total foto tidak melebihi tiga.';
                    return;
                }
                updatePhotoCounter();
                photoMessage.textContent = input.checked
                    ? 'Foto ditandai untuk dihapus saat perubahan disimpan.'
                    : 'Foto lama akan tetap dipertahankan.';
            });
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
