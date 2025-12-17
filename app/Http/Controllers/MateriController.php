<?php

namespace App\Http\Controllers;

use App\Models\Materi;
use App\Models\MateriFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class MateriController extends Controller
{
    public function index()
    {
        $materi = Materi::with('files')->orderBy('created_at', 'desc')->get();
        return view('admin.materi', compact('materi'));
    }

    public function show($id)
    {
        $materi = Materi::with('files')->find($id);
        if (!$materi) return response()->json(['success' => false], 404);
        return response()->json(['success' => true, 'data' => $materi]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul' => 'required|string|max:225',
            'deskripsi' => 'required|string',
            'files.*' => 'nullable|file|mimes:pdf,mp4,jpg,jpeg,png,webp,doc,docx,ppt,pptx|max:51200',
            'links.*' => 'nullable|url',
        ]);

        try {
            $materi = Materi::create([
                'judul_materi' => $request->judul,
                'deskripsi' => $request->deskripsi,
                'tgl_up' => now()->format('Y-m-d'),
                'id_user' => Auth::id(),
            ]);

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    // Upload ke Cloudinary
                    $uploadedFile = $file->storeOnCloudinary('materi');
                    
                    $publicId = $uploadedFile->getPublicId();
                    $secureUrl = $uploadedFile->getSecureUrl(); 
                    $ext = strtolower($file->getClientOriginalExtension());
                    
                    $tipe = match ($ext) {
                        'pdf' => 'pdf',
                        'mp4' => 'mp4',
                        'jpg', 'jpeg', 'png', 'webp' => 'gambar',
                        default => 'lainnya',
                    };

                    MateriFile::create([
                        'id_materi' => $materi->id_materi,
                        'file_path' => $publicId, // Simpan Public ID untuk hapus nanti
                        'link_url' => $secureUrl, // Simpan URL agar JS tidak perlu merakit ulang
                        'tipe_file' => $tipe,
                    ]);
                }
            }

            if ($request->links) {
                foreach ($request->links as $link) {
                    if (!empty($link)) {
                        MateriFile::create([
                            'id_materi' => $materi->id_materi,
                            'link_url' => $link,
                            'tipe_file' => 'link',
                        ]);
                    }
                }
            }

            return response()->json(['success' => true, 'message' => 'Materi berhasil ditambahkan!', 'data' => $materi->load('files')]);
        } catch (\Exception $e) {
            Log::error('Store Materi Error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menyimpan: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $materi = Materi::with('files')->find($id);
        if (!$materi) return response()->json(['success' => false], 404);

        $request->validate([
            'judul_materi' => 'required|string',
            'deskripsi' => 'nullable|string',
            'new_files.*' => 'nullable|file|max:51200',
        ]);

        try {
            $materi->update([
                'judul_materi' => $request->judul_materi,
                'deskripsi' => $request->deskripsi,
            ]);

            // Hapus file terpilih
            if ($request->deleted_files) {
                $deletedIds = json_decode($request->deleted_files, true);
                if (is_array($deletedIds)) {
                    $filesToDelete = MateriFile::whereIn('id_file', $deletedIds)->get();
                    foreach ($filesToDelete as $file) {
                        if ($file->tipe_file !== 'link' && $file->file_path) {
                            try {
                                // Hapus dari Cloudinary
                                Cloudinary::adminApi()->deleteAssets([$file->file_path]);
                            } catch (\Exception $e) {
                                Log::warning("Gagal hapus aset Cloudinary: " . $file->file_path);
                            }
                        }
                        $file->delete();
                    }
                }
            }

            // Tambah file baru
            if ($request->hasFile('new_files')) { 
                foreach ($request->file('new_files') as $file) {
                    $uploadedFile = $file->storeOnCloudinary('materi');
                    $ext = strtolower($file->getClientOriginalExtension());
                    
                    $tipe = match ($ext) {
                        'pdf' => 'pdf',
                        'mp4' => 'mp4',
                        'jpg', 'jpeg', 'png', 'webp' => 'gambar',
                        default => 'lainnya',
                    };

                    MateriFile::create([
                        'id_materi' => $materi->id_materi,
                        'file_path' => $uploadedFile->getPublicId(),
                        'link_url' => $uploadedFile->getSecureUrl(),
                        'tipe_file' => $tipe,
                    ]);
                }
            }
            
            // Tambah link baru
            if ($request->new_links) {
                foreach ($request->new_links as $link) {
                    if (!empty($link)) {
                        MateriFile::create([
                            'id_materi' => $materi->id_materi,
                            'link_url' => $link,
                            'tipe_file' => 'link',
                        ]);
                    }
                }
            }

            return response()->json(['success' => true, 'message' => 'Berhasil diperbarui!', 'data' => $materi->load('files')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $materi = Materi::with('files')->find($id);
        if (!$materi) return response()->json(['success' => false], 404);

        try {
            foreach ($materi->files as $file) {
                if ($file->tipe_file !== 'link' && $file->file_path) {
                     try {
                        Cloudinary::adminApi()->deleteAssets([$file->file_path]);
                    } catch (\Exception $e) {}
                }
                $file->delete();
            }
            $materi->delete();
            return response()->json(['success' => true, 'message' => 'Materi dihapus.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal hapus.'], 500);
        }
    }
}