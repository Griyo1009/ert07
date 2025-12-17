<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary; // Tambahkan ini

class ProfileController extends Controller
{
    // ... (Fungsi index dan update biodata tidak berubah)

    /**
     * Update foto profil pengguna (DIMODIFIKASI).
     */
    public function updatePhoto(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'foto_profil' => 'required|image|mimes:jpg,jpeg,png|max:2048' // Max 2MB
        ]);

        // 1. Hapus foto lama dari Cloudinary
        if ($user->foto_profil && $user->foto_profil !== 'default.png') {
            // Asumsi foto_profil menyimpan Public ID Cloudinary
            try {
                Cloudinary::destroy($user->foto_profil);
            } catch (\Exception $e) {
                // Log error jika penghapusan gagal, tapi lanjutkan
                \Log::warning('Gagal menghapus foto profil lama dari Cloudinary: ' . $e->getMessage());
            }
        }

        // 2. Upload foto baru ke Cloudinary
        $file = $request->file('foto_profil');
        // GANTI: Menggunakan storeOnCloudinary()
        $uploadedFile = $file->storeOnCloudinary('profiles'); // Folder 'profiles'

        // Ambil Public ID untuk disimpan di database
        $publicId = $uploadedFile->getPublicId();
        // Ambil URL Aman untuk respons klien
        $secureUrl = $uploadedFile->getSecureUrl(); 

        $user->foto_profil = $publicId; // Simpan Public ID
        $user->save();
        
        // Mengembalikan path lengkap (URL Aman) untuk update di client side
        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui.',
            // GANTI: Menggunakan URL Aman Cloudinary
            'image' => $secureUrl 
        ]);
    }
}