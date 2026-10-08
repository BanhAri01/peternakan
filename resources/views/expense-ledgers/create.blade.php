@extends('layouts.app')

@section('title', 'Catat Pengeluaran')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Catat Pengeluaran" subtitle="Untuk biaya selain pakan. Pembelian pakan dicatat di menu Belanja Pakan & Telur." icon="bi-receipt" :back="route('expenses.index')" back-label="Buku Kas" />

<x-alerts />

<form action="{{ route('expenses.store') }}" method="POST">
    @csrf
    @include('expense-ledgers._form')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('expenses.index') }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan Pengeluaran</button>
    </div>
</form>
@endsection
