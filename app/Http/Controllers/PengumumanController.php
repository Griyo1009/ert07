<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
// PENTING: Import Facade Cloudinary agar tidak error
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class PengumumanController extends Controller
{
    /**
     * Menampilkan halaman daftar pengumuman.
     */
    public function index()
    {
        $pengumuman = Pengumuman::latest()->get();
        return view('admin.pengumuman', compact('pengumuman'));
    }

    /**
     * Menampilkan detail satu pengumuman (JSON) untuk Modal Edit.
     */
    public function show($id)
    {
        $pengumuman = Pengumuman::find($id);
        if (!$pengumuman) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
        }
        return response()->json($pengumuman);
    }

    /**
     * Menyimpan pengumuman baru.
     */
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'tgl_pelaksanaan' => 'required|string', 
            'lokasi' => 'required|string',
            'tgl_pengumuman' => 'nullable|date', 
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $idUser = Auth::id();
        if (!$idUser) {
            return response()->json(['success' => false, 'message' => 'User belum login.'], 401);
        }

        // 2. Upload Gambar ke Cloudinary (PERBAIKAN DI SINI)
        $gambarPublicId = null;
        if ($request->hasFile('gambar')) {
            try {
                // Upload file langsung ke Cloudinary folder 'pengumuman'
                $uploadedFile = Cloudinary::upload($request->file('gambar')->getRealPath(), [
                    'folder' => 'pengumuman'
                ]);
                // Simpan Public ID (bukan path lokal)
                $gambarPublicId = $uploadedFile->getPublicId();
            } catch (\Exception $e) {
                Log::error('Gagal upload gambar pengumuman: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengupload gambar ke server.',
                ], 500);
            }
        }

        // 3. Simpan Data ke Database
        $data = [
            'id_user' => $idUser,
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'tgl_pengumuman' => $request->tgl_pengumuman ?? now(),
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
            Log::error('Pengumuman store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server saat menyimpan pengumuman.',
            ], 500);
        }
    }

    /**
     * Memperbarui pengumuman.
     */
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

        // Proses Update Gambar (PERBAIKAN DI SINI)
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama di Cloudinary jika ada
            if ($pengumuman->gambar) {
                try {
                    Cloudinary::destroy($pengumuman->gambar);
                } catch (\Exception $e) {
                    Log::warning('Gagal hapus gambar lama: ' . $e->getMessage());
                }
            }

            // Upload gambar baru
            try {
                $uploadedFile = Cloudinary::upload($request->file('gambar')->getRealPath(), [
                    'folder' => 'pengumuman'
                ]);
                $validated['gambar'] = $uploadedFile->getPublicId();
            } catch (\Exception $e) {
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

    /**
     * Menghapus pengumuman.
     */
    public function destroy($id)
    {
        try {
            $pengumuman = Pengumuman::find($id);

            if (!$pengumuman) {
                return response()->json(['success' => false, 'message' => 'Pengumuman tidak ditemukan.'], 404);
            }

            // Hapus gambar di Cloudinary
            if ($pengumuman->gambar) {
                try {
                    Cloudinary::destroy($pengumuman->gambar);
                } catch (\Exception $e) {
                    Log::warning('Gagal menghapus gambar Cloudinary: ' . $e->getMessage());
                }
            }

            $judul = $pengumuman->judul;
            $pengumuman->delete();

            return response()->json([
                'success' => true,
                'message' => "Pengumuman '{$judul}' berhasil dihapus.",
                'deleted_id' => $id,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus pengumuman.'], 500);
        }
    }
}