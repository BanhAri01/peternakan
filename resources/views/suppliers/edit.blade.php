@extends('layouts.app')

@section('title', 'Ubah Pemasok')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Ubah data pemasok" :subtitle="$supplier->name" icon="bi-pencil-square" :back="route('suppliers.index')" />

<x-alerts />

<x-panel>
    <form action="{{ route('suppliers.update', $supplier) }}" method="POST">
        @csrf
        @method('PUT')
        <x-field label="Nama pemasok" name="name" required>
            <input type="text" id="name" name="name" value="{{ old('name', $supplier->name) }}" class="form-control" required>
        </x-field>
        <div class="field">
            <span class="field-label">Menjual apa?</span>
            <div class="choices">
                @foreach(['feed' => 'Pakan', 'egg' => 'Telur', 'both' => 'Pakan & Telur'] as $val => $lbl)
                    <label class="choice"><input type="radio" name="type" value="{{ $val }}" @checked(old('type', $supplier->type) === $val)><span>{{ $lbl }}</span></label>
                @endforeach
            </div>
        </div>
        <x-field label="Nomor HP / WhatsApp" name="phone" optional>
            <input type="tel" id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}" class="form-control">
        </x-field>
        <x-field label="Alamat" name="address" optional>
            <textarea id="address" name="address" rows="2" class="form-control">{{ old('address', $supplier->address) }}</textarea>
        </x-field>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('suppliers.index') }}" class="btn btn-light btn-lg">Batal</a>
            <button type="submit" class="btn btn-primary btn-lg flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan</button>
        </div>
    </form>
</x-panel>
@endsection
