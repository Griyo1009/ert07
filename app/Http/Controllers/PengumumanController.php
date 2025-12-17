<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
// Import Cloudinary
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class PengumumanController extends Controller
{
    public function index()
    {
        $pengumuman = Pengumuman::latest()->get();
        return view('admin.pengumuman', compact('pengumuman'));
    }

    public function show($id)
    {
        $pengumuman = Pengumuman::find($id);
        if (!$pengumuman) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
        }
        return response()->json($pengumuman);
    }

    public function store(Request $request)
    {
        // 1. Validasi
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'tgl_pelaksanaan' => 'required|string',
            'lokasi' => 'required|string',
            'tgl_pengumuman' => 'nullable|date',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // 2. Ambil ID User (Pastikan user login)
        $idUser = Auth::id();
        if (!$idUser) {
            return response()->json(['success' => false, 'message' => 'Sesi habis, silakan login ulang.'], 401);
        }

        // 3. Upload Gambar ke Cloudinary (BAGIAN PENTING)
        $gambarPublicId = null;
        if ($request->hasFile('gambar')) {
            try {
                // Upload ke Cloudinary
                $uploadedFile = Cloudinary::upload($request->file('gambar')->getRealPath(), [
                    'folder' => 'pengumuman'
                ]);
                
                // Ambil Public ID (bukan path lokal)
                $gambarPublicId = $uploadedFile->getPublicId();
            } catch (\Exception $e) {
                // Log error untuk debugging di Vercel
                Log::error('Gagal upload gambar pengumuman: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengupload gambar ke Cloudinary.',
                ], 500);
            }
        }

        // 4. Simpan Data ke Database
        $data = [
            'id_user' => $idUser,
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            // Gunakan tanggal hari ini jika kosong
            'tgl_pengumuman' => $request->tgl_pengumuman ?? now()->format('Y-m-d'), 
            'tgl_pelaksanaan' => $validated['tgl_pelaksanaan'],
            'lokasi' => $validated['lokasi'],
            'gambar' => $gambarPublicId, // Simpan Public ID Cloudinary
        ];

        try {
            $pengumuman = Pengumuman::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Pengumuman berhasil ditambahkan!',
                'data' => $pengumuman,
            ]);
        } catch (\Exception $e) {
            Log::error('Error Simpan DB: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data ke database.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $pengumuman = Pengumuman::find($id);

        if (!$pengumuman) {
            return response()->json(['success' => false, 'message' => 'Pengumuman tidak ditemukan.'], 404);
        }

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'tgl_pelaksanaan' => 'required|date',
            'lokasi' => 'required|string',
        ]);

        // LOGIKA GAMBAR BARU (CLOUDINARY)
        if ($request->hasFile('gambar')) {
            // 1. Hapus gambar lama di Cloudinary jika ada
            if ($pengumuman->gambar) {
                try {
                    Cloudinary::destroy($pengumuman->gambar);
                } catch (\Exception $e) {
                    Log::warning('Gagal hapus gambar lama: ' . $e->getMessage());
                }
            }

            // 2. Upload gambar baru ke Cloudinary
            try {
                $uploadedFile = Cloudinary::upload($request->file('gambar')->getRealPath(), [
                    'folder' => 'pengumuman'
                ]);
                $validated['gambar'] = $uploadedFile->getPublicId();
            } catch (\Exception $e) {
                Log::error("Gagal upload update: " . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Gagal upload gambar baru.'], 500);
            }
        }

        try {
            $pengumuman->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Pengumuman berhasil diperbarui.',
                'data' => $pengumuman
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal update database.'], 500);
        }
    }

    public function destroy($id)
    {
        $pengumuman = Pengumuman::find($id);
        if (!$pengumuman) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($pengumuman->gambar) {
            try {
                Cloudinary::destroy($pengumuman->gambar);
            } catch (\Exception $e) {}
        }
        
        $pengumuman->delete();
        return response()->json(['success' => true, 'message' => 'Berhasil dihapus.']);
    }
}