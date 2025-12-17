<?php

use App\Http\Middleware\AdminAccess;
use App\Http\Middleware\UserAccess; 
use App\Http\Middleware\CheckSession;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\WargaController;
use App\Http\Controllers\MateriController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KomentarController;
use App\Http\Controllers\PengumumanController;


Route::get('/debug-ssl-path', function () {
    $path = env('DB_SSL_CA'); // Akan mengambil /tmp/isrgrootx1.pem

    if (!$path) {
        return "Error: Variabel DB_SSL_CA tidak diatur di environment.";
    }

    if (file_exists($path)) {
        // Cek apakah PHP bisa membaca file
        $readable = is_readable($path);
        
        // Cek ukuran file (untuk memastikan bukan file kosong/corrupt)
        $size = filesize($path); 

        return [
            'status' => 'SUCCESS',
            'message' => 'File sertifikat ditemukan dan dapat dibaca.',
            'path_terbaca' => $path,
            'is_readable' => $readable ? 'Ya' : 'TIDAK',
            'file_size_bytes' => $size,
            'file_size_kb' => round($size / 1024, 2) . ' KB',
        ];

    } else {
        return [
            'status' => 'ERROR',
            'message' => 'File sertifikat TIDAK DITEMUKAN di lokasi: ' . $path,
            'path_diuji' => $path
        ];
    }
});

// ===== Halaman Awal =====
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// ===== AUTH SECTION =====
Route::controller(AuthController::class)->group(function () {
    // Login & Logout
    Route::get('/login', 'showLogin')->name('login')->middleware(CheckSession::class);
    Route::post('/login', 'login')->name('login.post')->middleware(CheckSession::class);
    Route::post('/logout', 'logout')->name('logout');

    // Register umum
    Route::get('/register', 'showRegister')->name('register')->middleware(CheckSession::class);
    Route::post('/register', 'doRegister')->name('register.post')->middleware(CheckSession::class);

    Route::get('/register/admin', 'showRegisterAdmin')->name('register.admin');
    Route::post('/register/admin', 'registerAdmin')->name('register.admin.post');

    // Lupa Password
    Route::get('/forgot-password', 'showForgotPassword')->name('password.request');
});

// ===== ADMIN SECTION =====
Route::prefix('admin')->controller(AdminController::class)->group(function () {
    Route::get('/home', 'index')->name('admin.home')->middleware(AdminAccess::class);
    Route::get('/pengumuman', 'pengumuman')->name('admin.pengumuman')->middleware(AdminAccess::class);
    Route::get('/materi', 'materi')->name('admin.materi')->middleware(AdminAccess::class);
    Route::get('/warga', 'warga')->name('admin.warga')->middleware(AdminAccess::class)  ;
    Route::get('/profil', 'profil')->name('admin.profil')->middleware(AdminAccess::class);
    // Route::get('/materi-lihat/{id_materi}', 'materi_lihat')->name('materi-lihat');

    Route::get('/daftar-materi', 'daftarMateri')->name('admin.daftar-materi');
    Route::get('/lihat-materi/{id_materi}', 'lihatMateri')->name('admin.lihat-materi');

    Route::post('/lihat-materi/{id_materi}/komentar', [KomentarController::class, 'kirimKomentar'])
        ->name('admin.komentar.kirim');

    Route::post('/komentar/buka/{id_komentar}', [AdminController::class, 'bukaKomentar'])
        ->name('admin.komentar.buka');


    // Route::get('/register/admin', 'AuthController@showRegisterAdmin')->name('register.admin');

    // Route::post('/register/admin', [AuthController::class, 'registerAdmin'])->name('register.admin.post');

});
// ===== PROFILE UPDATE =====
Route::put('/profile/update', [ProfileController::class, 'update'])->name('admin.profile.update');
Route::put('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('admin.profile.update.photo');

// ===== PENGUMUMAN ADMIN =====
Route::prefix('admin/pengumuman')->group(function () {
    Route::get('/', [PengumumanController::class, 'index'])->name('pengumuman.index');
    Route::get('/data', [PengumumanController::class, 'fetch'])->name('pengumuman.fetch');
    Route::post('/', [PengumumanController::class, 'store'])->name('pengumuman.store');
    Route::get('/{id}', [PengumumanController::class, 'show'])->name('pengumuman.show');
    Route::put('/{id}', [PengumumanController::class, 'update'])->name('pengumuman.update');
    Route::delete('/{id}', [PengumumanController::class, 'destroy'])->name('pengumuman.delete');
});

// ===== MATERI ADMIN =====
Route::prefix('admin/materi')->middleware(['auth'])->group(function () {
    Route::get('/', [MateriController::class, 'index'])->name('materi.index');
    Route::get('/data', [MateriController::class, 'fetch'])->name('materi.fetch');
    Route::get('/show/{id}', [MateriController::class, 'show'])->name('materi.show'); // <--- tambahan penting
    Route::post('/store', [MateriController::class, 'store'])->name('materi.store');
    Route::put('/update/{id}', [MateriController::class, 'update'])->name('materi.update');
    Route::delete('/delete/{id}', [MateriController::class, 'destroy'])->name('materi.delete');
    Route::delete('/file/{id}', [MateriController::class, 'destroyFile'])->name('materi.file.delete');
});




// ===== STATUS AKUN DI ADMIN =====
Route::put('/admin/warga/blokir/{id_user}', [AdminController::class, 'blokir'])->name('admin.blokir');
Route::put('/admin/warga/terima/{id_user}', [AdminController::class, 'terima'])->name('admin.terima');
Route::put('/admin/warga/buka/{id_user}', [AdminController::class, 'buka'])->name('admin.buka');

// ===== WARGA SECTION =====
Route::prefix('warga')->controller(WargaController::class)->group(function () {
    Route::get('/homepage', 'homepage')->name('warga.homepage')->middleware(UserAccess::class);
    Route::get('/pengumuman', 'pengumuman')->name('warga.pengumuman')->middleware(UserAccess::class);
    Route::get('/materi', 'materi')->name('warga.materi')->middleware(UserAccess::class);
    Route::get('/lihat-materi/{id_materi}', 'lihat_materi')->name('warga.lihat-materi')->middleware(UserAccess::class);
    Route::get('/profil-warga', 'profil_warga')->name('warga.profil-warga')->middleware(UserAccess::class);
    Route::get('/edit-profil-warga', 'edit_profil_warga')->name('warga.edit-profil-warga')->middleware(UserAccess::class);
    Route::post('/warga/komentar/{id_materi}', [WargaController::class, 'kirimKomentar'])
        ->name('warga.komentar.kirim');

});

