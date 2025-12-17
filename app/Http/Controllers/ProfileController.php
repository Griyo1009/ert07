<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ProfileController extends Controller
{
    public function index()
    {
        return view('admin.admin-profile');
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->nama_lengkap = $request->nama_lengkap;
        $user->email = $request->email;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => $user
        ]);
    }

    public function updatePhoto(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'foto_profil' => 'required|image|mimes:jpg,jpeg,png|max:2048' 
        ]);

        try {
            // 1. Hapus foto lama jika bukan default
            if ($user->foto_profil && $user->foto_profil !== 'default.png') {
                try {
                    // Gunakan Admin API untuk menghapus asset berdasarkan Public ID
                    Cloudinary::adminApi()->deleteAssets([$user->foto_profil]);
                } catch (\Exception $e) {
                    \Log::warning('Gagal menghapus foto lama: ' . $e->getMessage());
                }
            }

            // 2. Upload foto baru menggunakan Helper Laravel
            // Ini otomatis mengembalikan objek CloudinaryEngine
            $uploadedFile = $request->file('foto_profil')->storeOnCloudinary('profiles');

            $publicId = $uploadedFile->getPublicId();
            $secureUrl = $uploadedFile->getSecureUrl();

            // 3. Simpan ke database
            $user->foto_profil = $publicId; 
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Foto profil berhasil diperbarui.',
                'image' => $secureUrl // Kirim URL lengkap ke JS untuk preview instan
            ]);

        } catch (\Exception $e) {
            \Log::error('Upload Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server: ' . $e->getMessage()
            ], 500);
        }
    }
}