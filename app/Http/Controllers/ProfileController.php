<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class ProfileController extends Controller
{
    /**
     * Menampilkan halaman profil admin.
     */
    public function index()
    {
        // Asumsi route ini mengembalikan view blade Anda
        return view('admin.admin-profile');
    }

    /**
     * Memperbarui biodata pengguna (Nama dan Email).
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Validasi, pastikan email unik kecuali email pengguna saat ini
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'email' => [
                'email'
            ],
        ]);

        $user->nama_lengkap = $request->nama_lengkap;
        $user->email = $request->email;
        
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diperbarui.',
            'data' => [
                'nama_lengkap' => $user->nama_lengkap,
                'email' => $user->email 
            ]
        ]);
    }

    /**
     * Update foto profil pengguna.
     */
    public function updatePhoto(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'foto_profil' => 'required|image|mimes:jpg,jpeg,png|max:2048' 
        ]);

        try {
            // 1. Hapus foto lama dari Cloudinary
            if ($user->foto_profil && $user->foto_profil !== 'default.png') {
                try {
                    // Gunakan uploadApi() untuk destroy juga agar konsisten
                    Cloudinary::uploadApi()->destroy($user->foto_profil);
                } catch (\Exception $e) {
                    \Log::warning('Gagal menghapus foto lama: ' . $e->getMessage());
                }
            }

            // 2. Upload foto baru (MENGGUNAKAN RAW API)
            // uploadApi()->upload() mengembalikan ARRAY, bukan Objek
            $result = Cloudinary::uploadApi()->upload($request->file('foto_profil')->getRealPath(), [
                'folder' => 'profiles'
            ]);

            // Ambil data dari Array result
            $publicId = $result['public_id'];      // Akses sebagai array
            $secureUrl = $result['secure_url'];    // Akses sebagai array

            // Simpan ke database
            $user->foto_profil = $publicId; 
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Foto profil berhasil diperbarui.',
                'image' => $secureUrl 
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