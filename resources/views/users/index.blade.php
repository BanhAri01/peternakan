@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
<x-page-header title="Pengguna" subtitle="Akun pemilik dan pekerja kandang. Pekerja masuk dengan memilih nama lalu mengetik PIN." icon="bi-people-fill">
    <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus-fill"></i> Tambah Pengguna</a>
</x-page-header>

<x-alerts />

<x-panel flush>
    @if($users->isEmpty())
        <x-empty icon="bi-people" title="Belum ada pengguna" />
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Nama</th><th>Peran</th><th>Cara masuk</th><th class="actions">Aksi</th></tr></thead>
                <tbody>
                    @foreach($users as $u)
                        <tr>
                            <td class="title-cell">
                                <span class="d-flex align-items-center gap-2">
                                    <span class="user-avatar" style="{{ $u->isOwner() ? '' : 'background:var(--egg)' }}">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                                    <b>{{ $u->name }}</b>
                                    @if($u->id === auth()->id())<x-tag tone="info">Anda</x-tag>@endif
                                </span>
                            </td>
                            <td data-label="Peran">
                                @if($u->isOwner())
                                    <x-tag tone="brand" icon="bi-shield-lock-fill">Pemilik</x-tag>
                                @else
                                    <x-tag tone="egg" icon="bi-person-badge-fill">Pekerja</x-tag>
                                @endif
                            </td>
                            <td data-label="Cara masuk">
                                @if($u->isOwner())
                                    {{ $u->email }}
                                @elseif($u->hasPin())
                                    <x-tag tone="success" icon="bi-key-fill">PIN sudah diatur</x-tag>
                                @else
                                    <x-tag tone="danger" icon="bi-exclamation-triangle-fill">Belum ada PIN — belum bisa masuk</x-tag>
                                @endif
                            </td>
                            <td class="actions">
                                <a href="{{ route('users.edit', $u) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil"></i> {{ $u->isWorker() && !$u->hasPin() ? 'Atur PIN' : 'Ubah' }}</a>
                                @if($u->id !== auth()->id())
                                    <x-delete-button :action="route('users.destroy', $u)" icon-only title="Hapus akun ini?" :message="'Akun ' . $u->name . ' akan dihapus dan tidak bisa masuk lagi. Riwayat panen yang pernah dicatat tetap tersimpan.'" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if($users->hasPages())
        <x-slot:footer>{{ $users->links() }}</x-slot:footer>
    @endif
</x-panel>
@endsection
