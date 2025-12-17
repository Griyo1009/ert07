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
        $pengumuman = Pengumuman::latest()->get();
        return view('admin.pengumuman', compact('pengumuman'));
    }

    public function show($id)
    {
        $pengumuman = Pengumuman::find($id);
        return $pengumuman 
            ? response()->json($pengumuman) 
            : response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
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

        $gambarPublicId = null;

        if ($request->hasFile('gambar')) {
            try {
                // Upload Gambar
                $uploadedFile = $request->file('gambar')->storeOnCloudinary('pengumuman');
                $gambarPublicId = $uploadedFile->getPublicId();
            } catch (\Exception $e) {
                Log::error('Gagal upload gambar: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Gagal upload gambar.'], 500);
            }
        }
        dd($gambarPublicId);

        try {
            $pengumuman = Pengumuman::create([
                'id_user' => Auth::id(),
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
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan database.'], 500);
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
            // Hapus gambar lama
            if ($pengumuman->gambar) {
                try {
                    Cloudinary::adminApi()->deleteAssets([$pengumuman->gambar]);
                } catch (\Exception $e) {
                    Log::warning('Gagal hapus gambar lama: ' . $e->getMessage());
                }
            }
            
            // Upload gambar baru
            try {
                $uploadedFile = $request->file('gambar')->storeOnCloudinary('pengumuman');
                $validated['gambar'] = $uploadedFile->getPublicId();
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Gagal upload gambar baru.'], 500);
            }
        }

        $pengumuman->update($validated);

        return response()->json([
            'success' => true, 
            'message' => 'Berhasil diperbarui!', 
            'data' => $pengumuman
        ]);
    }

    public function destroy($id)
    {
        $pengumuman = Pengumuman::find($id);
        if (!$pengumuman) return response()->json(['message' => 'Tidak ditemukan'], 404);

        if ($pengumuman->gambar) {
            try {
                Cloudinary::adminApi()->deleteAssets([$pengumuman->gambar]);
            } catch (\Exception $e) {}
        }
        
        $pengumuman->delete();
        return response()->json(['success' => true, 'message' => 'Berhasil dihapus.']);
    }
}