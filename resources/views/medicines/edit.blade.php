@extends('layouts.app')

@section('title', 'Ubah Obat')
@section('content-class', 'narrow')

@section('content')
<x-page-header :title="'Ubah ' . $medicine->name" icon="bi-pencil-square" :back="route('medicines.index')" back-label="Stok Obat" />

<x-alerts />

<form action="{{ route('medicines.update', $medicine) }}" method="POST">
    @csrf
    @method('PUT')
    @include('medicines._form')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('medicines.index') }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button>
    </div>
</form>

<div class="mt-3">
    <x-delete-button :action="route('medicines.destroy', $medicine)" label="Hapus dari daftar" :small="false" title="Hapus obat ini?" :message="'Hanya bisa jika ' . $medicine->name . ' belum punya riwayat keluar-masuk.'" />
</div>
@endsection
