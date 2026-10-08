@extends('layouts.app')

@section('title', 'Ubah Pengguna')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Ubah Pengguna" :subtitle="$user->name" icon="bi-pencil-square" :back="route('users.index')" back-label="Daftar pengguna" />

<x-alerts />

<form action="{{ route('users.update', $user) }}" method="POST">
    @csrf
    @method('PUT')
    @include('users._form')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('users.index') }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button>
    </div>
</form>
@endsection
