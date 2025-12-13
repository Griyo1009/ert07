@extends('layouts.warga-layout')

@section('page-title', 'Materi Warga')

@push('styles')
    <style>
        /* CSS Tambahan */
        
        /* Gaya untuk pemisah tanggal (separator) */
        .date-separator {
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #ced4da; /* Garis pemisah yang lebih halus */
        }
        
        /* Kontainer Utama Materi per Tanggal */
        .materi-group-container {
            background-color: #f8f9fa; /* Warna latar belakang yang lebih terang */
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem; /* Jarak antar grup tanggal */
        }

        /* Gaya untuk Card Materi */
        .materi-card {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
        }

        .materi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        /* Gaya untuk Tombol (menggunakan kelas outline gradient) */
        .btn-outline-gradient {
            color: #162660;
            border: 2px solid #162660;
            border-radius: 8px;
            width: 100px;
            transition: all 0.2s ease;
        }

        .btn-outline-gradient:hover {
            background: linear-gradient(to bottom, #162660, #2D4EC6);
            color: white;
            border-color: #162660;
        }
    </style>
@endpush

@section('content')

    <div class="container py-4">

        <h4 class="fw-bold mb-4 gradient-text">
            Materi Arsip
        </h4>
        
        {{-- Logika untuk mengelompokkan materi per tanggal --}}
        @php
            // Mengelompokkan berdasarkan tanggal, gunakan Y-m-d untuk sorting
            $materi_by_date = $materi->groupBy(function ($item) {
                return \Carbon\Carbon::parse($item->created_at)->format('Y-m-d');
            });
            
            $materi_by_date = $materi_by_date->sortKeysDesc();
        @endphp
        
        @forelse ($materi_by_date as $tanggal_sort => $list)
            {{-- Konversi tanggal Y-m-d ke format tampilan --}}
            @php
                $tanggal_tampil = \Carbon\Carbon::parse($tanggal_sort)->translatedFormat('d F Y');
            @endphp

            {{-- Separator Tanggal --}}
            <p class="fs-5 fw-semibold text-secondary date-separator mb-3">
                <i class="bi bi-calendar-event me-2"></i> {{ $tanggal_tampil }}
            </p>

            {{-- Kontainer Grup Materi --}}
            <div class="materi-group-container">
                @foreach ($list as $m)
                    <div class="card materi-card py-3 px-4 mb-3">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                            
                            {{-- Judul Materi --}}
                            <div class="mb-2 mb-md-0 flex-grow-1">
                                <i class="bi bi-file-earmark-text me-2 text-primary"></i>
                                <h5 class="card-title d-inline fw-medium text-dark">{{ $m->judul_materi }}</h5>
                            </div>
                            
                            {{-- Tombol Aksi --}}
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <span class="text-muted small me-2 d-none d-lg-block">
                                    {{ $m->files->count() }} Lampiran
                                </span>
                                
                                <a href="{{ route('warga.lihat-materi', $m->id_materi) }}" 
                                   class="btn btn-sm btn-outline-gradient">
                                    <i class="bi bi-eye"></i> Lihat
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
        @empty
            <div class="alert alert-secondary text-center" role="alert">
                <i class="bi bi-archive me-2"></i> Belum ada materi yang dipublikasikan.
            </div>
        @endforelse

    </div>

@endsection