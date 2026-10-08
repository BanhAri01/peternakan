@extends('layouts.app')

@section('title', 'Jenis Telur')
@section('content-class', 'narrow')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Jenis Telur (Grade)" subtitle="Jenis telur hasil sortir, misalnya Besar, Sedang, Kecil, Retak. Panen selalu dicatat sebagai Telur Campur lalu dipilah di menu Sortir Telur." icon="bi-egg-fill" />

<x-alerts />

<x-panel title="Tambah jenis telur" icon="bi-plus-circle-fill" tone="egg">
    <form action="{{ route('grades.store') }}" method="POST" class="row g-3 align-items-end">
        @csrf
        <div class="col-md-6">
            <x-field label="Nama jenis telur" name="name" required class="mb-0">
                <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control" placeholder="Contoh: Telur Besar" required>
            </x-field>
        </div>
        <div class="col-md-3">
            <x-field label="Kode singkat" name="code" optional class="mb-0">
                <input type="text" id="code" name="code" value="{{ old('code') }}" class="form-control" placeholder="B" maxlength="20">
            </x-field>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-egg w-100"><i class="bi bi-plus-lg"></i> Tambah</button>
        </div>
    </form>
</x-panel>

<x-panel title="Daftar jenis telur" icon="bi-list-ul" flush>
    @if($grades->isEmpty())
        <x-empty icon="bi-egg" title="Belum ada jenis telur" />
    @else
        <ul class="alert-list">
            @foreach($grades as $grade)
                <li x-data="{ edit: false }">
                    <span class="dot {{ $grade->is_active ? 'success' : 'info' }}"><i class="bi bi-egg-fill"></i></span>
                    <div class="txt">
                        <div x-show="!edit">
                            <b>{{ $grade->name }} @if($grade->code)<span class="text-muted fw-normal">({{ $grade->code }})</span>@endif</b>
                            <span>
                                Stok tercatat: {{ Format::number($grade->stock_kg, 1) }} kg ·
                                @if($grade->is_mixed)
                                    Hasil panen sebelum disortir (otomatis)
                                @else
                                    {{ $grade->is_active ? 'Tampil di formulir' : 'Disembunyikan' }}
                                @endif
                            </span>
                        </div>
                        <div x-show="edit" x-cloak><form action="{{ route('grades.update', $grade) }}" method="POST" class="d-flex flex-wrap gap-2">
                            @csrf
                            @method('PUT')
                            <input type="text" name="name" value="{{ $grade->name }}" class="form-control" style="flex:2 1 160px" required aria-label="Nama jenis telur">
                            <input type="text" name="code" value="{{ $grade->code }}" class="form-control" style="flex:1 1 70px" placeholder="Kode" aria-label="Kode">
                            <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                            <button type="button" class="btn btn-light btn-sm" @click="edit = false">Batal</button>
                        </form></div>
                    </div>
                    <div x-show="!edit" class="flex-shrink-0"><div class="d-flex gap-2">
                        <button type="button" class="btn btn-light btn-sm" @click="edit = true"><i class="bi bi-pencil"></i> Ganti nama</button>
                        @unless($grade->is_mixed)
                            <form action="{{ route('grades.toggle', $grade) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $grade->is_active ? 'btn-ghost-danger' : 'btn-success' }}">
                                    {{ $grade->is_active ? 'Sembunyikan' : 'Tampilkan' }}
                                </button>
                            </form>
                        @endunless
                    </div></div>
                </li>
            @endforeach
        </ul>
    @endif
</x-panel>

<div class="help-tip">
    <i class="bi bi-info-circle-fill"></i>
    <span>Jenis telur tidak bisa dihapus agar riwayat panen dan penjualan tetap utuh. Jika tidak dipakai lagi, tekan <b>Sembunyikan</b>.</span>
</div>
@endsection
