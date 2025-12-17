@extends('layouts.admin-layout')

@section('title', 'Profil | Dashboard Admin RT 07')

@push('styles')
    <style>
    .profile-wave-separator {
        position: relative;
        height: 60px;
        background-color: #162660; 
    }
    .profile-wave-separator::after {
        content: '';
        position: absolute;
        bottom: -1px; 
        left: 0;
        right: 0;
        height: 60px; 
        background-image: url("data:image/svg+xml;charset=utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1000 100' preserveAspectRatio='none'><path d='M0 100 L1000 100 L1000 0 C750 60 250 60 0 0 L0 100' fill='%23f8f9fa'/></svg>");
        background-size: 100% 100%;
    }

    </style>
@endpush
@section('content')

@php
$user = Auth::user();
$defaultImagePath = asset('images/profile-default.jpg'); 

// Pastikan Anda memanggil Facade Cloudinary dengan namespace penuh
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

// LOGIKA BARU UNTUK FOTO PROFIL DARI CLOUDINARY
$profileImagePath = $defaultImagePath;
if ($user->foto_profil && $user->foto_profil !== 'default.png') {
    // $user->foto_profil sekarang berisi Public ID Cloudinary.
    // Gunakan Cloudinary::getUrl() untuk menghasilkan URL aman.
    try {
        // Tambahkan transformasi dasar (opsional: crop, resize)
        $profileImagePath = Cloudinary::getUrl($user->foto_profil);
        
        // Contoh: Jika ingin melakukan crop dan resize (opsional, sesuaikan)
        // $profileImagePath = Cloudinary::getApi()->url->secureUrl($user->foto_profil, [
        //    'width' => 150, 
        //    'height' => 150, 
        //    'crop' => 'fill'
        // ]);
        
    } catch (\Exception $e) {
        // Jika terjadi error saat generate URL, gunakan default
        $profileImagePath = $defaultImagePath;
    }
}
@endphp

<div class="mx-auto pt-5 pb-5" style="background: #162660;"> {{-- Warna BG gelap #162660 --}}

    {{-- Form Pengunggahan Foto Profil --}}
    <form action="{{ route('admin.profile.update.photo') }}" method="POST" enctype="multipart/form-data"
        id="photo-upload-form">
        @csrf
        @method('PUT') {{-- Method spoofing untuk update --}}
        <div class="text-center mb-2">

            {{-- TAMBAHKAN WRAPPER UNTUK BOX-SHADOW --}}
            <div class="d-inline-block position-relative profile-picture-container mb-3">
                <img src="{{ $profileImagePath }}" alt="Profile Photo" class="rounded-circle"
                    style="width: 200px; height: 200px; object-fit: cover;" id="profile-image-preview">
            </div>

            <div>
                {{-- Tombol Unggah Foto (Menggunakan kelas btn-light) --}}
                <label for="foto_profil" class="btn btn-light px-4 btn-shadow-outline me-2">
                    Unggah Foto
                </label>
                <input type="file" name="foto_profil" id="foto_profil" class="d-none" accept="image/*">

                {{-- Tombol Simpan Foto (Menggunakan kelas btn-solid-shadow) --}}
                <button type="submit" class="btn btn-solid-shadow d-none px-4" id="savePhotoBtn">Simpan Foto</button>
            </div>
        </div>
    </form>

</div>

{{-- GARIS PEMISAH DENGAN WAVE/SHAPE --}}
<div class="profile-wave-separator"></div>

{{-- HEADER BIODATA DIRI --}}
<div class="card-header gradient-text fw-semibold ps-5 pt-2 mx-0 bg-white ">
    BIODATA DIRI
</div>

{{-- BAGIAN BIODATA --}}
<div class="mx-auto px-5 pt-3 pb-5" style="background-color: #f8f9fa;">
    {{-- Ganti BG di sini agar Biodata terlihat di atas #D0E6FD --}}
    <div class="card border-0 shadow my-3">
        {{-- Form Update Biodata --}}
        <form action="{{ route('admin.profile.update') }}" method="POST" class="p-3 border rounded bg-light"
            id="biodata-form">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="nama_lengkap" class="form-label fw-semibold">Nama Lengkap</label>
                <input type="text" class="form-control editable-field" name="nama_lengkap" id="nama_lengkap"
                    value="{{ old('nama_lengkap', $user->nama_lengkap) }}" readonly>
                @error('nama_lengkap')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="nik" class="form-label fw-semibold">NIK</label>
                {{-- NIK selalu readonly --}}
                <input type="text" class="form-control" id="nik" value="{{ old('NIK', $user->NIK) }}" readonly>
                @error('NIK')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email</label>
                <input type="email" class="form-control editable-field" name="email" id="email"
                    value="{{ old('email', $user->email) }}" readonly>
                @error('email')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="text-center mt-4" id="btn-group">
                <button type="button" class="btn btn-warning px-4 text-white" id="btnEdit">Edit</button>

                <div id="editActions" class="d-none">
                    <button type="button" class="btn btn-secondary px-4 me-2" id="btnCancel">Batal</button>
                    {{-- Tombol Simpan --}}
                    <button type="submit" class="btn btn-gradient-outline px-4"><span>Simpan</span></button>
                </div>
            </div>

        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Logika untuk menampilkan preview foto dan tombol simpan
    document.addEventListener('DOMContentLoaded', function () {
        const fotoProfilInput = document.getElementById('foto_profil');
        const profileImagePreview = document.getElementById('profile-image-preview');
        const savePhotoBtn = document.getElementById('savePhotoBtn');

        fotoProfilInput.addEventListener('change', function (event) {
            if (event.target.files && event.target.files[0]) {
                const reader = new FileReader();

                reader.onload = function (e) {
                    profileImagePreview.src = e.target.result;
                    // Tampilkan tombol Simpan Foto setelah file dipilih
                    savePhotoBtn.classList.remove('d-none');
                    savePhotoBtn.classList.add('d-inline-block');
                }

                reader.readAsDataURL(event.target.files[0]);
            }
        });

        // Logika Edit/Batal Biodata (Asumsi ini ada di profil.js Anda, tapi disertakan di sini jika profil.js belum diisi)
        const btnEdit = document.getElementById('btnEdit');
        const btnCancel = document.getElementById('btnCancel');
        const editActions = document.getElementById('editActions');
        const editableFields = document.querySelectorAll('.editable-field');

        let originalValues = {};

        btnEdit.addEventListener('click', function () {
            // Sembunyikan tombol Edit, tampilkan tombol Simpan/Batal
            btnEdit.classList.add('d-none');
            editActions.classList.remove('d-none');

            // Aktifkan field
            editableFields.forEach(field => {
                originalValues[field.id] = field.value; // Simpan nilai asli
                field.removeAttribute('readonly');
            });
        });

        btnCancel.addEventListener('click', function () {
            // Tampilkan tombol Edit, sembunyikan tombol Simpan/Batal
            btnEdit.classList.remove('d-none');
            editActions.classList.add('d-none');

            // Nonaktifkan field dan kembalikan nilai
            editableFields.forEach(field => {
                field.setAttribute('readonly', true);
                field.value = originalValues[field.id]; // Kembalikan nilai asli
            });
        });

    });

</script>
<script src="{{ asset('js/profil.js') }}"></script>
@endpush
