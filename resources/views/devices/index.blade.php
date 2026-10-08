@extends('layouts.app')

@section('title', 'HP Kandang')
@section('content-class', 'narrow')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="HP Kandang" subtitle="HP atau tablet tempat pekerja masuk. Pekerja tidak perlu email: cukup tekan nama lalu ketik PIN." icon="bi-phone-fill" />

<x-alerts />

{{-- Status HP yang sedang dipakai --}}
@if($thisIsRegistered)
    <x-panel tone="brand">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="stat-icon" style="width:52px;height:52px;font-size:1.5rem;background:var(--success-soft);color:var(--success)"><i class="bi bi-phone-fill"></i></span>
            <div class="flex-grow-1">
                <div class="fw-800 fs-5">HP ini sudah menjadi HP kandang</div>
                <div class="text-muted">"{{ $current->name }}" · pekerja bisa masuk di sini dengan nama + PIN.</div>
            </div>
            <form action="{{ route('devices.hand-over') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary btn-lg" data-no-lock><i class="bi bi-box-arrow-right"></i> Serahkan ke pekerja</button>
            </form>
        </div>
        <div class="field-hint mt-2">"Serahkan ke pekerja" = Anda keluar, lalu layar masuk menampilkan nama-nama pekerja.</div>
    </x-panel>
@else
    <x-panel title="Jadikan HP ini HP kandang" icon="bi-plus-circle-fill" tone="egg">
        <ol class="mb-3 ps-3">
            <li>Buka aplikasi ini di HP yang akan dipakai pekerja di kandang.</li>
            <li>Masuk sebagai pemilik (seperti sekarang).</li>
            <li>Beri nama HP, lalu tekan tombol di bawah.</li>
        </ol>
        <form action="{{ route('devices.store') }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-sm-7">
                <x-field label="Nama HP" name="name" class="mb-0">
                    <input type="text" id="name" name="name" maxlength="60" class="form-control" placeholder="Contoh: HP Kandang A, Tablet Gudang">
                </x-field>
            </div>
            <div class="col-sm-5">
                <button type="submit" class="btn btn-egg btn-lg w-100"><i class="bi bi-phone"></i> Jadikan HP kandang</button>
            </div>
        </form>
    </x-panel>
@endif

@if($workersWithPin === 0)
    <div class="notice notice-warning">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <div>Belum ada pekerja yang punya PIN. Tambahkan pekerja dan PIN-nya di menu <a href="{{ route('users.index') }}">Pengguna</a> agar bisa masuk.</div>
    </div>
@endif

<x-panel title="Daftar HP kandang" icon="bi-list-ul" flush>
    @if($devices->isEmpty())
        <x-empty icon="bi-phone" title="Belum ada HP kandang">Daftarkan HP pertama dengan tombol di atas.</x-empty>
    @else
        <ul class="alert-list">
            @foreach($devices as $d)
                <li x-data="{ edit: false }">
                    <span class="dot {{ $d->is_active ? 'success' : 'info' }}"><i class="bi {{ $d->is_active ? 'bi-phone-fill' : 'bi-phone' }}"></i></span>
                    <div class="txt">
                        <div x-show="!edit">
                            <b>{{ $d->name }} @if($current && $current->id === $d->id)<x-tag tone="info">HP ini</x-tag>@endif @unless($d->is_active)<x-tag tone="neutral">Dicabut</x-tag>@endunless</b>
                            <span>
                                Terakhir dipakai: {{ $d->last_seen_at ? $d->last_seen_at->diffForHumans() : '-' }}
                                · didaftarkan {{ Format::date($d->created_at) }}{{ $d->creator ? ' oleh ' . $d->creator->name : '' }}
                            </span>
                        </div>
                        <div x-show="edit" x-cloak><form action="{{ route('devices.update', $d) }}" method="POST" class="d-flex gap-2">
                            @csrf
                            @method('PUT')
                            <input type="text" name="name" value="{{ $d->name }}" maxlength="60" class="form-control" required aria-label="Nama HP">
                            <button class="btn btn-primary btn-sm">Simpan</button>
                            <button type="button" class="btn btn-light btn-sm" @click="edit = false">Batal</button>
                        </form></div>
                    </div>
                    @if($d->is_active)
                        <div x-show="!edit" class="flex-shrink-0"><div class="d-flex gap-2">
                            <button type="button" class="btn btn-light btn-sm" @click="edit = true"><i class="bi bi-pencil"></i></button>
                            <form action="{{ route('devices.destroy', $d) }}" method="POST" data-confirm="Pekerja tidak bisa masuk lagi di HP ini. Lakukan jika HP hilang, rusak, atau dijual." data-confirm-title="Cabut {{ $d->name }}?" data-confirm-button="Ya, cabut">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-ghost-danger btn-sm">Cabut</button>
                            </form>
                        </div></div>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</x-panel>
@endsection
