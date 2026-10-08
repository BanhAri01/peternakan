@extends('layouts.app')

@section('title', 'Riwayat Perubahan')

@php use App\Models\ActivityLog; @endphp

@section('content')
<x-page-header title="Riwayat Perubahan" subtitle="Semua kegiatan tercatat otomatis: siapa menambah, mengubah, atau menghapus data, dan kapan." icon="bi-clock-history" />

<x-alerts />

<x-panel>
    <form method="GET" class="filter-bar">
        <x-field label="Dari tanggal" for="start">
            <input type="date" id="start" name="start" value="{{ $start->toDateString() }}" class="form-control">
        </x-field>
        <x-field label="Sampai tanggal" for="end">
            <input type="date" id="end" name="end" value="{{ $end->toDateString() }}" class="form-control">
        </x-field>
        <x-field label="Orang" for="user">
            <select id="user" name="user" class="form-select">
                <option value="">Semua orang</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" @selected(request('user') == $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="Jenis data" for="jenis">
            <select id="jenis" name="jenis" class="form-select">
                <option value="">Semua data</option>
                @foreach($types as $key => $label)
                    <option value="{{ $key }}" @selected(request('jenis') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>
        <x-field label="Kegiatan" for="aksi">
            <select id="aksi" name="aksi" class="form-select">
                <option value="">Semua kegiatan</option>
                @foreach($events as $key => $event)
                    <option value="{{ $key }}" @selected(request('aksi') === $key)>{{ ucfirst($event[0]) }}</option>
                @endforeach
            </select>
        </x-field>
        <div class="btns">
            <button type="submit" class="btn btn-primary" data-no-lock><i class="bi bi-search"></i> Tampilkan</button>
            <a href="{{ route('activity.index') }}" class="btn btn-light" title="Atur ulang"><i class="bi bi-arrow-counterclockwise"></i></a>
        </div>
    </form>
</x-panel>

<x-panel title="Kegiatan" icon="bi-list-check" flush>
    @if($logs->isEmpty())
        <x-empty icon="bi-clock" title="Belum ada kegiatan pada rentang ini" />
    @else
        <div class="table-wrap">
            <table class="tbl stack">
                <thead><tr><th>Waktu</th><th>Orang</th><th>Kegiatan</th><th>Rincian</th></tr></thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td class="title-cell">
                                <div><b>{{ $log->created_at->translatedFormat('d M Y') }}</b><div class="text-muted small">{{ $log->created_at->format('H:i') }} WITA</div></div>
                            </td>
                            <td data-label="Orang"><b>{{ $log->user_name ?? 'Sistem' }}</b></td>
                            <td data-label="Kegiatan">
                                <x-tag :tone="$log->event_tone" :icon="$log->event_icon">{{ ucfirst($log->event_label) }}</x-tag>
                                <div class="mt-1">{{ $log->summary }}</div>
                            </td>
                            <td data-label="Rincian">
                                @if($log->changes)
                                    <details>
                                        <summary class="small fw-semibold">Lihat {{ count($log->changes) }} isian</summary>
                                        <ul class="small mb-0 mt-1 ps-3">
                                            @foreach($log->changes as $field => $value)
                                                <li>
                                                    <span class="text-muted">{{ ActivityLog::fieldLabel($field) }}:</span>
                                                    @if(is_array($value))
                                                        @if($value[0] !== null)<s class="text-danger">{{ $value[0] }}</s> →@endif
                                                        <b>{{ $value[1] ?? '(kosong)' }}</b>
                                                    @else
                                                        <b>{{ $value }}</b>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if($logs->hasPages())
        <x-slot:footer>{{ $logs->links() }}</x-slot:footer>
    @endif
</x-panel>
@endsection
