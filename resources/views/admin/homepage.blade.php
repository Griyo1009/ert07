@extends('layouts.admin-layout')

@section('title', 'Dashboard Admin RT 07')

@push('styles')
  <style>
    .header-bg {
      background: url('{{ asset('images/login-bg.png') }}') center/cover no-repeat;
      height: 230px;
      position: relative
    }

    .btn-outline-dark:hover {
      background-color: white;
      color: rgb(123, 123, 123);
    }
  </style>
@endpush

@section('content')
  <!-- HEADER -->
  <div class="header-bg d-flex align-items-end justify-content-center text-white">
    <div class="header-overlay"></div>
    <div class="position-absolute bottom-0 mb-4 text-center">
      <h4 class="fw-semibold text-white text-shadow">Selamat Datang! Admin RT 07</h4>
    </div>
  </div>

  <!-- DATA STATISTIK -->
  <div class="bg-white shadow mt-n5 overflow-hidden position-relative w-100" style="border-radius: 0;">
    <!-- Judul Section -->
    <div class="text-white fw-bold px-4 py-2" style="background: linear-gradient(to bottom, #162660, #2D4EC6);">
      DATA STATISTIK
    </div>

    <!-- Isi Statistik -->
    <div class="row text-center p-4 mx-0">
      <!-- Warga Aktif -->
      <div class="col-md-6 border-lg-end border-dark d-flex flex-column justify-content-between">
        <div class="text-start">
          <small class="text-muted">Warga Aktif</small>
        </div>
        <div class="text-center d-flex justify-content-center align-items-center gap-2">
          <h3 class="fw-bold mb-0">{{ $warga_aktif }} Warga</h3>
          <i class="bi bi-people-fill fs-3"></i>
        </div>
      </div>

      <!-- Materi -->
      <div class="col-md-6 d-flex flex-column justify-content-between">
        <div class="text-start">
          <small class="text-muted">Materi Yang Sudah Diupload</small>
        </div>
        <div class="text-center d-flex justify-content-center align-items-center gap-2">
          <h3 class="fw-bold mb-0">{{ $materi_upload }} Materi</h3>
          <i class="bi bi-journal-bookmark-fill fs-3"></i>
        </div>
      </div>
    </div>
    <hr>

    <!-- Komentar Section -->
    <!-- Komentar Section -->
    <div class="px-4 pb-4">
      <p class="mb-2 text-muted text-start">Komentar Belum Dibaca ({{ $komentar_baru->count() }})</p>

      <ul class="list-group">
        @forelse ($komentar_baru as $k)
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <strong>{{ $k->user->username ?? 'Admin' }}</strong> di materi
              <em>{{ $k->materi->judul_materi ?? '-' }}</em>
            </div>
            <div class="text-end">
              <form action="{{ route('admin.komentar.buka', $k->id_komentar) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm text-white btn-warning">
                  Buka
                </button>
              </form>
            </div>
          </li>
        @empty
          <li class="list-group-item text-muted">Tidak ada komentar baru.</li>
        @endforelse
      </ul>
    </div>





  </div>


  <!-- AKSES CEPAT -->
  {{-- <div class="container my-5">
    <h6 class="fw-bold mb-4 text-uppercase text-muted">Akses Cepat</h6>
    <div class="row g-4 justify-content-center">
      <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100 text-center">
          <img src="{{ asset('images/pengumuman.png') }}" class="card-img-top" alt="Pengumuman">
          <div class="card-body">
            <h6 class="fw-semibold">Pengumuman</h6>
            <a href="pengumuman" class="btn btn-gradient-outline mt-2 px-4"><span>Tambah</span></a>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100 text-center">
          <img src="{{ asset('images/materi.png') }}" class="card-img-top" alt="Materi">
          <div class="card-body">
            <h6 class="fw-semibold">Materi</h6>
            <a href="materi" class="btn btn-gradient-outline mt-2 px-4"><span>Tambah</span></a>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100 text-center">
          <img src="{{ asset('images/warga.png') }}" class="card-img-top" alt="Warga">
          <div class="card-body">
            <h6 class="fw-semibold">Warga</h6>
            <a href="warga" class="btn btn-gradient-outline mt-2 px-4"><span>Kelola</span></a>
          </div>
        </div>
      </div>
    </div>
  </div> --}}
 
  <script>
    window.addEventListener('pageshow', function (event) {
      if (event.persisted || performance.getEntriesByType("navigation")[0].type === "back_forward") {
        location.reload();
      }
    });
  </script>

@endsection