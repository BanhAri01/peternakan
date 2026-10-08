@extends('layouts.app')

@section('title', 'Ubah Pengeluaran')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Ubah Pengeluaran" :subtitle="$expense->item_name" icon="bi-pencil-square" :back="route('expenses.index')" back-label="Buku Kas" />

<x-alerts />

<form action="{{ route('expenses.update', $expense) }}" method="POST">
    @csrf
    @method('PUT')
    @include('expense-ledgers._form')
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('expenses.index') }}" class="btn btn-light btn-xl">Batal</a>
        <button type="submit" class="btn btn-primary btn-xl flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan Perubahan</button>
    </div>
</form>
@endsection
