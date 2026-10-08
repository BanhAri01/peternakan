@extends('layouts.app')

@section('title', 'Tambah Pengguna')
@section('content-class', 'narrow')

@php $user = new \App\Models\User(['role' => 'worker']); @endphp

@section('content')
<x-page-header title="Tambah Pengguna" subtitle="Buat akun untuk pekerja kandang atau pemilik lain." icon="bi-person-plus-fill" :back="route('users.index')" back-label="Daftar pengguna" />

<x-alerts />

<form action="{{ route('users.store') }}" method="POST">
    @csrf
    @include('users._form')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('users.index') }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan Pengguna</button>
    </div>
</form>
@endsection
