@extends('layouts.app')

@section('title', 'Catat Pendapatan Lain')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Catat Pendapatan Lain" subtitle="Uang masuk selain jual telur: ayam afkir, kotoran, karung bekas, dll." icon="bi-cash-coin" :back="route('other-incomes.index')" back-label="Pendapatan Lain" />

<x-alerts />

<form action="{{ route('other-incomes.store') }}" method="POST">
    @csrf
    @include('other-incomes._form')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('other-incomes.index') }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan Pendapatan</button>
    </div>
</form>
@endsection
