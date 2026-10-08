@extends('layouts.app')

@section('title', 'Ubah Kandang')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Ubah Kandang" :subtitle="$coop->name" icon="bi-pencil-square" :back="route('coops.show', $coop)" back-label="Kembali ke detail kandang" />

<x-alerts />

<form action="{{ route('coops.update', $coop) }}" method="POST">
    @csrf
    @method('PUT')
    @include('coops._form')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('coops.show', $coop) }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button>
    </div>
</form>
@endsection
