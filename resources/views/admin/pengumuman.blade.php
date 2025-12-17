@extends('layouts.admin-layout')

@section('title', 'Pengumuman | Dashboard Admin RT 07')

@push('styles')
    <style>
        body {
            background-color: #f5f6fa;
            font-family: 'Roboto', sans-serif;
        }

        .btn-danger {
            background: linear-gradient(to bottom, #dc3545, #a71d2a);
            border-color: #dc3545;
            width: 100px;
        }

        .btn-danger:hover {
            background: linear-gradient(to bottom, #a71d2a, #dc3545);
            border-color: #a71d2a;
        }

        .btn-warning {
            background: linear-gradient(to bottom, #162660, #2D4EC6);
            border-color: #162660;
            width: 100px;
        }

        @media (max-width: 576px) {

            .btn-danger,
            .btn-warning {
                width: auto;
                padding-left: 12px;
                padding-right: 12px;
            }
        }
    </style>
@endpush

@php
    use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
    $CLOUDINARY_CLOUD_NAME = env('CLOUDINARY_CLOUD_NAME'); // Ambil Cloud Name untuk JS
@endphp

@section('content')
    <div class="container-fluid p-0 mb-5">
        <!-- ===== HEADER ===== -->
        <div class="p-3">
            <div class="fw-bold fs-6 ms-2" style="color: #162660;">PENGUMUMAN</div>
        </div>
        <!-- ===== TAMBAH PENGUMUMAN ===== -->
        <div class="pb-1 pt-4 mb-4" style="background: linear-gradient(to bottom, #162660, #2D4EC6);">
            <div class="bg-white shadow-sm rounded mx-4 p-3 mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <p class="fw-normal mb-2 mb-sm-0">Pengumuman Baru</p>
                    <button id="toggleFormBtn" class="btn btn-gradient-outline">
                        Tambah <i class="bi bi-plus-circle ms-2"></i>
                    </button>
                </div>

                <!-- ===== FORM TAMBAH ===== -->
                <form id="formPengumuman" enctype="multipart/form-data" class="p-3 border rounded bg-light mt-3 d-none">
                    @csrf
                    <input type="hidden" name="id_user" value="{{ Auth::user()->id_user }}">

                    <div class="mb-3">
                        <label for="gambar" class="form-label fw-semibold">Unggah Gambar</label>
                        <input type="file" name="gambar" id="gambar" class="form-control" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label for="judul" class="form-label fw-semibold">Judul</label>
                        <input type="text" name="judul" id="judul" class="form-control"
                            placeholder="Masukkan judul pengumuman" required>
                    </div>

                    <div class="mb-3">
                        <label for="isi" class="form-label fw-semibold">Isi</label>
                        <textarea name="isi" id="isi" class="form-control" rows="4" placeholder="Tulis isi pengumuman..."
                            required></textarea>
                    </div>


                    <div class="mb-5">
                        <label for="tgl_pelaksanaan" class="form-label fw-semibold">Tanggal Pelaksanaan</label>
                        <input type="date" name="tgl_pelaksanaan" id="tgl_pelaksanaan" class="form-control"
                            value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="mb-3">
                        <label for="lokasi" class="form-label fw-semibold">Lokasi Pelaksanaan</label>
                        <textarea name="lokasi" id="lokasi" class="form-control" rows="4"
                            placeholder="Tulis Lokasi pelaksanaan..." required></textarea>
                    </div>



                    <div class="mb-5" hidden>
                        <label for="tgl_pengumuman" class="form-label fw-semibold">Tanggal</label>
                        <input type="date" name="tgl_pengumuman" id="tgl_pengumuman" class="form-control"
                            value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" id="cancelForm" class="btn btn-secondary" style="width: 200px;">Batal</button>
                        <button type="submit" class="btn btn-warning text-white" style="width: 200px;">Tambah</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== LIST PENGUMUMAN ===== -->
        <div class="container mt-5 mx-6">
            <h6 class="fw-bold mb-3 text-muted text-uppercase">Baru Ditambahkan</h6>

            <div id="listPengumuman">
                @foreach($pengumuman as $item)
                    <div class="card mb-3 shadow-sm pengumuman-item" data-id="{{ $item->id_pengumuman }}">
                        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                            <div class="d-flex flex-column flex-md-row align-items-start gap-3 w-100 ">
                                @php
                                    // Logika: Jika ada Public ID (gambar), buat URL Cloudinary, jika tidak, pakai default lokal
                                    $imageUrl = $item->gambar 
                                                ? Cloudinary::getUrl($item->gambar) 
                                                : asset('images/default.jpg'); // Asumsi Anda punya default.jpg di public/images
                                @endphp
                                <img src="{{ $imageUrl }}" alt="Gambar Pengumuman"
                                    class="rounded" style="width:200px; height:150px; object-fit:cover;">
                                <div class="flex-grow-1">
                                    <h5 class="fw-bold mb-4">{{ $item->judul }}</h5>
                                    <p class="mb-4">{{ \Illuminate\Support\Str::limit($item->isi, 120) }}</p>
                                    <small class="text-muted">Tanggal: {{ $item->tgl_pengumuman }}</small>
                                </div>
                            </div>

                            <div class="d-flex gap-2 align-self-md-center">
                                <button class="btn btn-warning text-white btn-edit"
                                    data-id="{{ $item->id_pengumuman }}">Edit</button>
                                <button type="submit" class="btn btn-danger btn-delete"
                                    data-id="{{ $item->id_pengumuman }}">Hapus</button>
                            </div>
                        </div>
                    </div>
                @endforeach
                <!-- ===== MODAL EDIT PENGUMUMAN ===== -->
                <div class="modal fade" id="editPengumumanModal" tabindex="-1" aria-labelledby="editModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header text-white"
                                style="background: linear-gradient(to bottom, #162660, #2D4EC6);">
                                <h5 class="modal-title" id="editModalLabel">Edit Pengumuman</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <form id="formEditPengumuman" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <div class="modal-body">
                                    <input type="hidden" id="edit_id" name="id_pengumuman">

                                    <div class="mb-3">
                                        <label for="edit_judul" class="form-label fw-semibold">Judul</label>
                                        <input type="text" id="edit_judul" name="judul" class="form-control" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="edit_isi" class="form-label fw-semibold">Isi</label>
                                        <textarea id="edit_isi" name="isi" class="form-control" rows="4"
                                            required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="edit_tgl_pelaksanaan" class="form-label fw-semibold">Tanggal
                                            Pelaksanaan</label>
                                        <input type="date" id="edit_tgl_pelaksanaan" name="tgl_pelaksanaan"
                                            class="form-control">
                                    </div>

                                    <div class="mb-3">
                                        <label for="edit_lokasi" class="form-label fw-semibold">Lokasi Pelaksanaan</label>
                                        <textarea id="edit_lokasi" name="lokasi" class="form-control" rows="4"
                                            required></textarea>
                                    </div>


                                    <div class="mb-3">
                                        <label for="edit_gambar" class="form-label fw-semibold">Ganti Gambar
                                            (Opsional)</label>
                                        <input type="file" id="edit_gambar" name="gambar" class="form-control"
                                            accept="image/*">
                                    </div>

                                    <div class="text-center">
                                        <img id="previewEditImage" src="" class="rounded"
                                            style="max-width: 200px; display:none;">
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-warning text-white" style="width: 200px;">Simpan
                                        Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

<script>
    const CLOUDINARY_CLOUD_NAME = '{{ $CLOUDINARY_CLOUD_NAME }}';
    const DEFAULT_IMAGE_URL = '{{ asset('images/default.jpg') }}';
</script>

@push('scripts')

    <!-- JS eksternal -->
    <script src="{{ asset('js/pengumuman.js') }}"></script>
@endpush