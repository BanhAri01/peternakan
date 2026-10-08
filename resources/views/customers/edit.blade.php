@extends('layouts.app')

@section('title', 'Ubah Pelanggan')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Ubah data pelanggan" :subtitle="$customer->name" icon="bi-pencil-square" :back="route('customers.show', $customer)" />

<x-alerts />

<x-panel>
    <form action="{{ route('customers.update', $customer) }}" method="POST">
        @csrf
        @method('PUT')
        <x-field label="Nama pelanggan" name="name" required>
            <input type="text" id="name" name="name" value="{{ old('name', $customer->name) }}" class="form-control" required>
        </x-field>
        <x-field label="Nomor HP / WhatsApp" name="phone" optional hint="Dipakai untuk mengirim tagihan lewat WhatsApp. Contoh: 081234567890">
            <input type="tel" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-control" placeholder="08xxxxxxxxxx">
        </x-field>
        <x-field label="Alamat" name="address" optional>
            <textarea id="address" name="address" rows="2" class="form-control">{{ old('address', $customer->address) }}</textarea>
        </x-field>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('customers.show', $customer) }}" class="btn btn-light btn-lg">Batal</a>
            <button type="submit" class="btn btn-primary btn-lg flex-grow-1"><i class="bi bi-check2-circle"></i> Simpan</button>
        </div>
    </form>
</x-panel>
@endsection
