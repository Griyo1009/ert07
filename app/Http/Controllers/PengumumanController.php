<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class PengumumanController extends Controller
{
    public function index()
    {
        // Menggunakan latest() agar pengumuman terbaru muncul paling atas
        $pengumuman = Pengumuman::latest()->get();
        return view('admin.pengumuman', compact('pengumuman'));
    }

    public function show($id)
    {
        $pengumuman = Pengumuman::find($id);
        
        if (!$pengumuman) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
        }
        
        // Opsional: Menambahkan URL gambar lengkap ke respon JSON agar mudah dipakai frontend
        $pengumuman->image_url = $pengumuman->gambar ? Cloudinary::getUrl($pengumuman->gambar) : null;

        return response()->json($pengumuman);
    }

    public function store(Request $request)
    {
        // Validasi diperbaiki (tgl_pelaksanaan dan tgl_pengumuman jadi date)
        $validated = $request->validate([
            'judul'           => 'required|string|max:255',
            'isi'             => 'required|string',
            'tgl_pelaksanaan' => 'required|date',
            'lokasi'          => 'required|string',
            'tgl_pengumuman'  => 'nullable|date',
            'gambar'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $gambarPublicId = null;

        // 1. Proses Upload Cloudinary
        if ($request->hasFile('gambar')) {
            try {
                // Upload dan ambil respon obyek Cloudinary
                $uploadedFile = $request->file('gambar')->storeOnCloudinary('pengumuman');
                // Ambil Public ID untuk disimpan di Database
                $gambarPublicId = $uploadedFile->getPublicId();
            } catch (\Exception $e) {
                Log::error('Gagal upload gambar Cloudinary: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Gagal upload gambar.'], 500);
            }
        }

        // 2. Simpan ke Database (TiDB/MySQL)
        try {
            $pengumuman = Pengumuman::create([
                'id_user'         => Auth::id(),
                'judul'           => $validated['judul'],
                'isi'             => $validated['isi'],
                'tgl_pengumuman'  => $request->tgl_pengumuman ?? now(), // Default now() jika kosong
                'tgl_pelaksanaan' => $validated['tgl_pelaksanaan'],
                'lokasi'          => $validated['lokasi'],
                'gambar'          => $gambarPublicId, // Menyimpan Public ID (bukan URL) agar mudah dihapus nanti
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengumuman berhasil ditambahkan!',
                'data'    => $pengumuman
            ]);

        } catch (\Exception $e) {
            // Jika DB gagal, sebaiknya hapus gambar yang barusan diupload ke Cloudinary agar tidak jadi sampah
            if ($gambarPublicId) {
                Cloudinary::destroy($gambarPublicId);
            }
            
            Log::error('DB Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan ke database.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $pengumuman = Pengumuman::find($id);
        if (!$pengumuman) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $validated = $request->validate([
            'judul'           => 'required|string|max:255',
            'isi'             => 'required|string',
            'tgl_pelaksanaan' => 'required|date',
            'lokasi'          => 'required|string',
            'gambar'          => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // Siapkan data update (kecuali gambar dulu)
        $dataToUpdate = [
            'judul'           => $validated['judul'],
            'isi'             => $validated['isi'],
            'tgl_pelaksanaan' => $validated['tgl_pelaksanaan'],
            'lokasi'          => $validated['lokasi'],
        ];

        // LOGIKA UPDATE GAMBAR
        if ($request->hasFile('gambar')) {
            try {
                // 1. Hapus gambar lama jika ada
                if ($pengumuman->gambar) {
                    Cloudinary::destroy($pengumuman->gambar);
                }

                // 2. Upload gambar baru
                $uploadedFile = $request->file('gambar')->storeOnCloudinary('pengumuman');
                
                // 3. Masukkan Public ID baru ke array update
                $dataToUpdate['gambar'] = $uploadedFile->getPublicId();

            } catch (\Exception $e) {
                Log::error('Gagal update gambar: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Gagal proses gambar baru.'], 500);
            }
        }

        // Update database
        try {
            $pengumuman->update($dataToUpdate);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil diperbarui!',
                'data'    => $pengumuman
            ]);
        } catch (\Exception $e) {
             return response()->json(['success' => false, 'message' => 'Gagal update database.'], 500);
        }
    }

    public function destroy($id)
    {
        $pengumuman = Pengumuman::find($id);
        if (!$pengumuman) return response()->json(['message' => 'Tidak ditemukan'], 404);

        try {
            // Hapus gambar di Cloudinary dulu
            if ($pengumuman->gambar) {
                Cloudinary::destroy($pengumuman->gambar);
            }
            
            // Baru hapus data di DB
            $pengumuman->delete();
            
            return response()->json(['success' => true, 'message' => 'Berhasil dihapus.']);
            
        } catch (\Exception $e) {
            Log::error('Gagal hapus data: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menghapus data.'], 500);
        }
    }
}