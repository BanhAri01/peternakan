@extends('layouts.app')

@section('title', 'Ubah Pendapatan Lain')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Ubah Pendapatan Lain" icon="bi-pencil-square" :back="route('other-incomes.index')" back-label="Pendapatan Lain" />

<x-alerts />

<form action="{{ route('other-incomes.update', $income) }}" method="POST">
    @csrf
    @method('PUT')
    @include('other-incomes._form')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('other-incomes.index') }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button>
    </div>
</form>
@endsection
