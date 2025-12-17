@extends('layouts.admin-layout')

@section('title', 'Materi | Dashboard Admin RT 07')

@push('styles')
<style>
    .materi-card {
        position: relative;
        border: none;
        border-radius: 12px;
        padding: 15px;
        background: #fff;
        transition: 0.3s ease;
    }

    .materi-card:hover {
        box-shadow: 0 4px 12px rgba(45, 78, 198, 0.2);
    }

    .file-item {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 10px;
        background: #f8f9fa;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .file-item i {
        font-size: 1.5rem;
        color: #2D4EC6;
    }

</style>
@endpush

@section('content')
<div class="bg-white shadow mt-n5 overflow-hidden position-relative w-100">
    <div class="p-3">
        <div class="fw-bold fs-6 ms-2" style="color: #162660;">MANAGE MATERI</div>
    </div>

    <div class="pb-1 pt-4 " style="background: linear-gradient(to bottom, #162660, #2D4EC6);">
        <div class="bg-white shadow-sm rounded mx-4 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <p class="fw-normal mb-2 mb-sm-0">Materi Baru</p>
                <button id="toggleFormBtn" class="btn btn-gradient-outline">
                    Tambah <i class="bi bi-plus-circle ms-2"></i>
                </button>
            </div>

            <!-- ===== FORM TAMBAH MATERI ===== -->
            <form id="formMateri" action="{{ route('materi.store') }}" method="POST" enctype="multipart/form-data"
                class="p-3 border rounded bg-light mt-3 d-none">
                @csrf
                <div class="mb-3">
                    <label for="judul" class="form-label fw-semibold">Judul Materi</label>
                    <input type="text" name="judul" id="judul" class="form-control" placeholder="Masukkan judul materi"
                        required>
                </div>

                <div class="mb-3">
                    <label for="deskripsi" class="form-label fw-semibold">Deskripsi Materi</label>
                    <textarea name="deskripsi" id="deskripsi" rows="3" class="form-control"
                        placeholder="Tuliskan deskripsi singkat..." required></textarea>
                </div>

                <div id="fileContainer" class="mb-3">
                    <label class="form-label fw-semibold">Unggah File Materi</label>
                    <div class="input-group mb-2 file-input-group">
                        <input type="file" name="files[]" class="form-control"
                            accept=".pdf,.jpg,.jpeg,.png,.mp4,.doc,.docx,.ppt,.pptx">
                        <button type="button" class="btn btn-outline-secondary btn-add-file"><i
                                class="bi bi-plus"></i></button>
                    </div>
                </div>

                <div id="linkContainer" class="mb-3">
                    <label class="form-label fw-semibold">Tambahkan Link (YouTube/Drive/Web)</label>
                    <div class="input-group mb-2">
                        <input type="url" name="links[]" class="form-control" placeholder="Masukkan link materi">
                        <button type="button" class="btn btn-outline-secondary btn-add-link"><i
                                class="bi bi-plus"></i></button>
                    </div>
                </div>

                <div class="d-flex justify-content-center gap-3">
                    <button type="button" id="cancelForm" class="btn btn-secondary" style="width: 200px;">Batal</button>
                    <button type="submit" class="btn btn-warning text-white" style="width: 200px;">Tambah</button>
                </div>
            </form>
            <!-- ===== MODAL EDIT MATERI ===== -->
            <div class="modal fade" id="editMateriModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content border-0 rounded-4 shadow-lg">
                        <div class="modal-header text-white"
                            style="background: linear-gradient(to bottom, #162660, #2D4EC6);">
                            <h5 class="modal-title">Edit Materi</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body overflow-auto" style="max-height: 70vh;">
                            <form id="formEditMateri" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" id="edit_id_materi" name="id_materi">

                                <div class="mb-3">
                                    <label for="edit_judul" class="form-label fw-semibold">Judul Materi</label>
                                    <input type="text" id="edit_judul" name="judul_materi" class="form-control"
                                        required>
                                </div>

                                <div class="mb-3">
                                    <label for="edit_deskripsi" class="form-label fw-semibold">Deskripsi</label>
                                    <textarea id="edit_deskripsi" name="deskripsi" rows="3"
                                        class="form-control"></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">File & Link Tersimpan</label>
                                    <div id="existingFiles" class="border rounded p-2 bg-light small text-muted">
                                        <em>Belum ada file.</em>
                                    </div>
                                </div>

                                <label class="mt-3">Tambah File Materi</label>
                                <div id="editFileWrapper">
                                    <div class="input-group mb-2">
                                        <input type="file" name="new_files[]" class="form-control">
                                        <button type="button"
                                            class="btn btn-outline-secondary btn-add-edit-file">+</button>
                                    </div>
                                </div>

                                <label class="mt-3">Tambah Link Materi (YouTube/Drive/Web)</label>
                                <div id="editLinkWrapper">
                                    <div class="input-group mb-2">
                                        <input type="text" name="new_links[]" class="form-control" placeholder="Masukkan link materi">
                                        <button type="button" class="btn btn-outline-secondary btn-add-edit-link">+</button>
                                    </div>
                                </div>
                                <input type="hidden" name="deleted_files" id="deleted_files">
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" form="formEditMateri" class="btn btn-warning text-white"
                                style="width: 200px;">Simpan
                                Perubahan</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ===== LIST MATERI ===== -->
<div class="container mt-5 mx-6" id="listMateri">
    <h6 class="fw-bold mb-3 text-uppercase text-muted">Daftar Materi</h6>
    @php
        use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
        $CLOUDINARY_CLOUD_NAME = env('CLOUDINARY_CLOUD_NAME');
    @endphp

    @foreach($materi as $item)
    <div class="materi-card mb-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h5 class="fw-bold">{{ $item->judul_materi }}</h5>
                <p class="text-muted mb-2">{{ $item->deskripsi }}</p>
                <small class="text-secondary d-block mb-2">Diupload pada {{ $item->tgl_up }}</small>
            </div>

            <div class="d-flex gap-2 align-self-center">
                <button class="btn btn-warning text-white btn-sm btn-edit"
                    data-id="{{ $item->id_materi }}">Edit</button>
                <button class="btn btn-danger btn-sm btn-delete" data-id="{{ $item->id_materi }}">Hapus</button>
            </div>
        </div>

        <div class="mt-3">
            @foreach($item->files as $f)
            <div class="file-item">
                @if($f->tipe_file == 'pdf')
                <i class="bi bi-file-earmark-pdf"></i>
                @php
                    $href = $f->link_url ?? ($f->file_path ? Cloudinary::getUrl($f->file_path) : '#');
                    $text = 'Lihat PDF: ' . \Illuminate\Support\Str::limit($f->file_path ?? 'File', 20);
                @endphp
                <a href="{{ $href }}" target="_blank" class="text-decoration-none">{{ $text }}</a>

                @elseif($f->tipe_file == 'mp4')
                <i class="bi bi-play-circle"></i>
                 @php
                    $href = $f->link_url ?? ($f->file_path ? Cloudinary::getUrl($f->file_path) : '#');
                    $text = 'Putar Video: ' . \Illuminate\Support\Str::limit($f->file_path ?? 'File', 20);
                @endphp
                <a href="{{ $href }}" target="_blank" class="text-decoration-none">{{ $text }}</a>

                @elseif($f->tipe_file == 'gambar')
                <i class="bi bi-image"></i>
                 @php
                    $href = $f->link_url ?? ($f->file_path ? Cloudinary::getUrl($f->file_path) : '#');
                    $text = 'Lihat Gambar: ' . \Illuminate\Support\Str::limit($f->file_path ?? 'File', 20);
                @endphp
                <a href="{{ $href }}" target="_blank" class="text-decoration-none">{{ $text }}</a>

                @elseif($f->tipe_file == 'link')
                <i class="bi bi-link-45deg"></i>
                <a href="{{ $f->link_url }}" target="_blank" class="text-decoration-none">{{ $f->link_url }}</a>
                @else
                <i class="bi bi-file-earmark-text"></i>
                @php
                    // Fallback untuk file yang tidak terdeteksi tipenya tapi memiliki Public ID/URL
                    $href = $f->link_url ?? ($f->file_path ? Cloudinary::getUrl($f->file_path) : '#');
                    $text = 'Unduh File: ' . \Illuminate\Support\Str::limit($f->file_path ?? 'File', 20);
                @endphp
                <a href="{{ $href }}" target="_blank" class="text-decoration-none">{{ $text }}</a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endforeach
</div>
@endsection

<script>
    const CLOUDINARY_CLOUD_NAME = '{{ $CLOUDINARY_CLOUD_NAME }}';
    const DEFAULT_IMAGE_URL = '{{ asset('images/default.jpg') }}';
</script>
@push('scripts')
<script src="{{ asset('js/materi.js') }}"></script>
<script>
    document.addEventListener("DOMContentLoaded", () => {
        const toggleBtn = document.getElementById("toggleFormBtn");
        const form = document.getElementById("formMateri");
        const cancelBtn = document.getElementById("cancelForm");

        if (!toggleBtn || !form || !cancelBtn) return;

        toggleBtn.addEventListener("click", () => {
            form.classList.toggle("d-none");
            toggleBtn.innerHTML = form.classList.contains("d-none") ?
                'Tambah <i class="bi bi-plus-circle ms-2"></i>' :
                'Tutup <i class="bi bi-chevron-up ms-2"></i>';
        });

        cancelBtn.addEventListener("click", () => {
            form.classList.add("d-none");
            toggleBtn.innerHTML = 'Tambah <i class="bi bi-plus-circle ms-2"></i>';
            form.reset();
        });

        // Tambah input link baru
        document.querySelector(".btn-add-link").addEventListener("click", () => {
            const container = document.getElementById("linkContainer");
            const newInput = document.createElement("div");
            newInput.classList.add("input-group", "mb-2");
            newInput.innerHTML = `
        <input type="url" name="links[]" class="form-control" placeholder="Masukkan link tambahan">
        <button type="button" class="btn btn-outline-danger btn-remove-link"><i class="bi bi-dash"></i></button>
      `;
            container.appendChild(newInput);
        });

        // Hapus input link tambahan
        document.addEventListener("click", (e) => {
            if (e.target.closest(".btn-remove-link")) {
                e.target.closest(".input-group").remove();
            }
        });
    });

    // ===== Tambah input file baru =====
    document.querySelector("#fileContainer") ?.addEventListener("click", (e) => {
        if (e.target.closest(".btn-add-file")) {
            const container = document.getElementById("fileContainer");
            const newInput = document.createElement("div");
            newInput.classList.add("input-group", "mb-2", "file-input-group");
            newInput.innerHTML = `
                <input type="file" name="files[]" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.mp4,.doc,.docx,.ppt,.pptx">
                <button type="button" class="btn btn-outline-danger btn-remove-file-input"><i class="bi bi-dash"></i></button>
            `;
            container.appendChild(newInput);
        }
        // Hapus input file
        if (e.target.closest(".btn-remove-file-input")) {
            e.target.closest(".file-input-group").remove();
        }
    });

</script>
@endpush
