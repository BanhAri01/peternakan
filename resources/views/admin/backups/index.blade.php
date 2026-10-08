@extends('layouts.app')

@section('title', 'Backup Database')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Backup Database" subtitle="Salinan seluruh data semua peternakan. Dibuat otomatis setiap hari pukul 01.30 WITA." icon="bi-database-fill-check">
    <form action="{{ route('admin.backups.store') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-primary"><i class="bi bi-database-add"></i> Backup Sekarang</button>
    </form>
</x-page-header>

<x-alerts />

@if($stale)
    <div class="notice notice-danger" role="alert">
        <i class="bi bi-exclamation-octagon-fill"></i>
        <div>
            <span class="notice-title">Backup otomatis tidak berjalan</span>
            Tidak ada backup dalam {{ config('hefam.backup.stale_hours') }} jam terakhir. Periksa Cron Job di cPanel:
            <code>cd ~/public_html && php artisan schedule:run</code> setiap menit.
        </div>
    </div>
@else
    <div class="notice notice-success" role="status">
        <i class="bi bi-shield-check"></i>
        <div>Backup otomatis berjalan normal. Backup terakhir {{ $files->first()['time']->diffForHumans() }}.</div>
    </div>
@endif

<x-panel title="File backup" :subtitle="'Disimpan ' . $keepDays . ' hari di ' . $directory . '. Unduh satu file setiap minggu dan simpan di Google Drive/laptop sebagai cadangan di luar server.'" icon="bi-archive" flush>
    @if($files->isEmpty())
        <x-empty icon="bi-database-x" title="Belum ada file backup">Tekan "Backup Sekarang" untuk membuat yang pertama.</x-empty>
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>File</th><th>Waktu</th><th class="num">Ukuran</th><th class="actions">Aksi</th></tr></thead>
                <tbody>
                    @foreach($files as $file)
                        <tr>
                            <td class="title-cell"><b>{{ $file['name'] }}</b></td>
                            <td data-label="Waktu">{{ $file['time']->translatedFormat('d M Y, H:i') }}<div class="text-muted small">{{ $file['time']->diffForHumans() }}</div></td>
                            <td data-label="Ukuran" class="num">{{ Format::fileSize($file['size']) }}</td>
                            <td class="actions">
                                <a href="{{ route('admin.backups.download', $file['name']) }}" class="btn btn-light btn-sm"><i class="bi bi-download"></i> Unduh</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-panel>
@endsection
