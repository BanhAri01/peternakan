@extends('layouts.app')

@section('title', 'Ubah Pakan')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Ubah Data Pakan" :subtitle="$feedStock->feed_name" icon="bi-pencil-square" :back="route('feed-stocks.index')" back-label="Stok Pakan" />

<x-alerts />

<div class="notice notice-warning">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <div>Ubah stok di sini hanya untuk membetulkan selisih setelah dihitung ulang di gudang. Untuk pakan yang baru datang, gunakan <a href="{{ route('procurement.index') }}">Belanja Pakan & Telur</a>.</div>
</div>

<form action="{{ route('feed-stocks.update', $feedStock) }}" method="POST">
    @csrf
    @method('PUT')
    @include('feed-stocks._form')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('feed-stocks.index') }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button>
    </div>
</form>
@endsection
