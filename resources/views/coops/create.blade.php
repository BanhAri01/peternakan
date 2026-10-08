@extends('layouts.app')

@section('title', 'Tambah Kandang')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Tambah Kandang" subtitle="Isi data kandang dan ayam yang baru masuk." icon="bi-house-add-fill" :back="route('coops.index')" back-label="Semua kandang" />

<x-alerts />

<form action="{{ route('coops.store') }}" method="POST">
    @csrf
    @include('coops._form')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('coops.index') }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan Kandang</button>
    </div>
</form>
@endsection
