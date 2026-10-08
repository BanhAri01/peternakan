@extends('layouts.app')

@section('title', 'Jenis Pakan Baru')
@section('content-class', 'narrow')

@php $feedStock = new \App\Models\FeedStock(); @endphp

@section('content')
<x-page-header title="Tambah Jenis Pakan" subtitle="Daftarkan jenis pakan yang dipakai di peternakan." icon="bi-box-seam-fill" :back="route('feed-stocks.index')" back-label="Stok Pakan" />

<x-alerts />

<form action="{{ route('feed-stocks.store') }}" method="POST">
    @csrf
    @include('feed-stocks._form')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('feed-stocks.index') }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan</button>
    </div>
</form>
@endsection
