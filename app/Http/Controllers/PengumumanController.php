<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Models\Pengumuman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\log;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary; // Tambahkan ini

class PengumumanController extends Controller
{
    // ... (Fungsi index, show, dan fetch tidak berubah)

    public function store(Request $request)
    {
        // validasi
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'tgl_pelaksanaan' => 'required|string',
            'lokasi' => 'required|string',
            'tgl_pengumuman' => 'required|date',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        // ... (Pengambilan id_user tidak berubah)

        // simpan file jika ada (DIMODIFIKASI)
        $gambarPublicId = null;
        if ($request->hasFile('gambar')) {
            // GANTI: Menggunakan storeOnCloudinary()
            $uploadedFile = $request->file('gambar')->storeOnCloudinary('pengumuman');
            $gambarPublicId = $uploadedFile->getPublicId(); // Simpan Public ID
        }

        // siapkan data
        $data = [
            'id_user' => $idUser,
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'tgl_pengumuman' => $validated['tgl_pengumuman'],
            'tgl_pelaksanaan' => $validated['tgl_pelaksanaan'],
            'lokasi' => $validated['lokasi'],
            // GANTI: Menyimpan Public ID Cloudinary
            'gambar' => $gambarPublicId, 
        ];

        try {
            $pengumuman = Pengumuman::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Pengumuman berhasil ditambahkan.',
                'data' => $pengumuman,
            ]);
        } catch (\Exception $e) {
            // ... (Error handling tidak berubah)
        }
    }


    public function update(Request $request, $id)
    {
        $pengumuman = Pengumuman::findOrFail($id);


        // Validasi ringan
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'tgl_pelaksanaan' => 'required|date',
            'lokasi' => 'required|string',
        ]);

        // Simpan gambar baru kalau ada (DIMODIFIKASI)
        if ($request->hasFile('gambar')) {
            // hapus gambar lama dari Cloudinary
            if ($pengumuman->gambar) {
                // GANTI: Menggunakan Cloudinary::destroy()
                Cloudinary::destroy($pengumuman->gambar);
            }

            // Upload gambar baru
            $uploadedFile = $request->file('gambar')->storeOnCloudinary('pengumuman');
            // GANTI: Menyimpan Public ID Cloudinary
            $validated['gambar'] = $uploadedFile->getPublicId(); 
        }

        // Update data ke DB
        $pengumuman->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pengumuman berhasil diperbarui.',
            'data' => $pengumuman
        ]);
    }


    public function destroy($id)
    {
        try {
            $pengumuman = Pengumuman::find($id);

            if (!$pengumuman) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengumuman tidak ditemukan atau sudah dihapus sebelumnya.',
                ], 404);
            }

            // Hapus gambar jika ada (DIMODIFIKASI)
            if ($pengumuman->gambar) {
                // GANTI: Menggunakan Cloudinary::destroy()
                Cloudinary::destroy($pengumuman->gambar);
            }

            $judul = $pengumuman->judul;
            $pengumuman->delete();

            return response()->json([
                'success' => true,
                'message' => "Pengumuman '{$judul}' berhasil dihapus.",
                'deleted_id' => $id,
            ]);
        } catch (\Exception $e) {
            // ... (Error handling tidak berubah)
        }
    }


}