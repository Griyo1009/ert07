<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
// Import Facade Cloudinary
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class PengumumanController extends Controller
{
    /**
     * Menampilkan halaman daftar pengumuman.
     */
    public function index()
    {
        // Ambil semua data pengumuman dari database, urutkan terbaru
        $pengumuman = Pengumuman::latest()->get();

        // Kirim ke view
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
     * API Fetch (jika diperlukan oleh JS).
     */
    public function fetch()
    {
        $data = Pengumuman::latest()->get();
        return response()->json($data);
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
            'tgl_pelaksanaan' => 'required|string', // Bisa date atau string tergantung input
            'lokasi' => 'required|string',
            // tgl_pengumuman kadang otomatis, tapi jika dari input:
            'tgl_pengumuman' => 'nullable|date', 
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // 2. Ambil ID User
        $idUser = Auth::id();
        if (!$idUser) {
            return response()->json([
                'success' => false,
                'message' => 'User belum terautentikasi.',
            ], 401);
        }

        // 3. Upload Gambar ke Cloudinary (Jika ada)
        $gambarPublicId = null;
        if ($request->hasFile('gambar')) {
            try {
                // Upload menggunakan Facade Cloudinary (lebih stabil)
                $uploadedFile = Cloudinary::upload($request->file('gambar')->getRealPath(), [
                    'folder' => 'pengumuman'
                ]);
                $gambarPublicId = $uploadedFile->getPublicId();
            } catch (\Exception $e) {
                Log::error('Gagal upload gambar pengumuman: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengupload gambar ke server.',
                ], 500);
            }
        }

        // 4. Siapkan Data
        $data = [
            'id_user' => $idUser,
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'tgl_pengumuman' => $request->tgl_pengumuman ?? now(), // Default now() jika kosong
            'tgl_pelaksanaan' => $validated['tgl_pelaksanaan'],
            'lokasi' => $validated['lokasi'],
            'gambar' => $gambarPublicId, // Simpan Public ID
        ];

        try {
            $pengumuman = Pengumuman::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Pengumuman berhasil ditambahkan.',
                'data' => $pengumuman,
            ]);
        } catch (\Exception $e) {
            Log::error('Pengumuman store error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan server saat menyimpan pengumuman.',
                'error' => $e->getMessage(),
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
            return response()->json([
                'success' => false,
                'message' => 'Pengumuman tidak ditemukan.',
            ], 404);
        }

        // Validasi
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'tgl_pelaksanaan' => 'required|date',
            'lokasi' => 'required|string',
        ]);

        // Simpan gambar baru kalau ada
        if ($request->hasFile('gambar')) {
            // 1. Hapus gambar lama di Cloudinary jika ada
            if ($pengumuman->gambar) {
                try {
                    Cloudinary::destroy($pengumuman->gambar);
                } catch (\Exception $e) {
                    Log::warning('Gagal hapus gambar lama: ' . $e->getMessage());
                }
            }

            // 2. Upload gambar baru
            try {
                $uploadedFile = Cloudinary::upload($request->file('gambar')->getRealPath(), [
                    'folder' => 'pengumuman'
                ]);
                $validated['gambar'] = $uploadedFile->getPublicId();
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal upload gambar baru.',
                ], 500);
            }
        }

        // Update data ke DB
        try {
            $pengumuman->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Pengumuman berhasil diperbarui.',
                'data' => $pengumuman
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal update database.',
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'Pengumuman tidak ditemukan atau sudah dihapus.',
                ], 404);
            }

            // Hapus gambar di Cloudinary jika ada
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
            Log::error('Gagal menghapus pengumuman: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus pengumuman.',
            ], 500);
        }
    }
}