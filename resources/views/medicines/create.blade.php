@extends('layouts.app')

@section('title', 'Tambah Obat')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Tambah Obat & Vitamin" icon="bi-capsule" :back="route('medicines.index')" back-label="Stok Obat" />

<x-alerts />

<form action="{{ route('medicines.store') }}" method="POST">
    @csrf
    @include('medicines._form')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('medicines.index') }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan & Isi Stok Awal</button>
    </div>
</form>
@endsection
