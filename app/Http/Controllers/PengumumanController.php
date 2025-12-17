<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
// Import Cloudinary Facade (WAJIB)
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
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'tgl_pelaksanaan' => 'required|string',
            'lokasi' => 'required|string',
            'tgl_pengumuman' => 'nullable|date',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $idUser = Auth::id();
        
        $gambarPublicId = null;

        // LOGIKA UPLOAD CLOUDINARY
        if ($request->hasFile('gambar')) {
            try {
                $uploadedFile = Cloudinary::upload($request->file('gambar')->getRealPath(), [
                    'folder' => 'pengumuman'
                ]);
                $gambarPublicId = $uploadedFile->getPublicId();
            } catch (\Exception $e) {
                Log::error('Gagal upload gambar: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Gagal upload gambar ke Cloudinary.'], 500);
            }
        }

        try {
            $pengumuman = Pengumuman::create([
                'id_user' => $idUser,
                'judul' => $validated['judul'],
                'isi' => $validated['isi'],
                'tgl_pengumuman' => $request->tgl_pengumuman ?? now(),
                'tgl_pelaksanaan' => $validated['tgl_pelaksanaan'],
                'lokasi' => $validated['lokasi'],
                'gambar' => $gambarPublicId,
            ]);

            return response()->json([
                'success' => true, 
                'message' => 'Pengumuman berhasil ditambahkan!', 
                'data' => $pengumuman
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal simpan DB: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan data.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $pengumuman = Pengumuman::find($id);
        if (!$pengumuman) return response()->json(['message' => 'Tidak ditemukan'], 404);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'tgl_pelaksanaan' => 'required|date',
            'lokasi' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // LOGIKA UPDATE GAMBAR CLOUDINARY
        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
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
                Log::error("Gagal upload update: " . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Gagal upload gambar baru.'], 500);
            }
        }

        try {
            $pengumuman->update($validated);

            return response()->json([
                'success' => true, 
                'message' => 'Berhasil diperbarui!', 
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