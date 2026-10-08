@extends('layouts.app')

@section('title', 'Absensi Pekerja')
@section('content-class', 'narrow')

@php use App\Models\Attendance; use App\Support\Format; @endphp

@section('content')
<x-page-header title="Absensi Pekerja" :subtitle="'Kehadiran tanggal ' . Format::dayDate($date) . '. Pekerja yang mencatat panen/sortir otomatis tercatat Hadir.'" icon="bi-calendar-check-fill">
    <a href="{{ route('payroll.index') }}" class="btn btn-light"><i class="bi bi-cash-stack"></i> Gaji Bulan Ini</a>
</x-page-header>

<x-alerts />

<x-panel>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <a href="{{ route('attendance.index') }}" class="btn {{ $date->isToday() ? 'btn-primary' : 'btn-light' }}">Hari ini</a>
        <a href="{{ route('attendance.index', ['tanggal' => today()->subDay()->toDateString()]) }}" class="btn {{ $date->isYesterday() ? 'btn-primary' : 'btn-light' }}">Kemarin</a>
        <form method="GET" action="{{ route('attendance.index') }}" class="ms-auto">
            <input type="date" name="tanggal" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" class="form-control" onchange="this.form.submit()" aria-label="Pilih tanggal lain">
        </form>
    </div>
</x-panel>

@if($workers->isEmpty())
    <x-panel>
        <x-empty icon="bi-people" title="Belum ada pekerja">
            Tambahkan pekerja di menu Pengguna terlebih dahulu.
            <x-slot:action><a href="{{ route('users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus-fill"></i> Tambah Pekerja</a></x-slot:action>
        </x-empty>
    </x-panel>
@else
    <form action="{{ route('attendance.store') }}" method="POST">
        @csrf
        <input type="hidden" name="work_date" value="{{ $date->toDateString() }}">

        @foreach($workers as $worker)
            @php $current = old('statuses.' . $worker->id, $records->get($worker->id)?->status); @endphp
            <x-panel :title="$worker->name" icon="bi-person-fill"
                     :subtitle="$records->get($worker->id)?->source === 'otomatis' ? 'Tercatat otomatis karena mencatat panen/sortir' : null">
                <div class="choices">
                    @foreach(Attendance::STATUSES as $key => $s)
                        <label class="choice">
                            <input type="radio" name="statuses[{{ $worker->id }}]" value="{{ $key }}" @checked($current === $key)>
                            <span><i class="bi {{ $s['icon'] }} text-{{ $s['tone'] === 'neutral' ? 'muted' : $s['tone'] }}"></i> {{ $s['label'] }}</span>
                        </label>
                    @endforeach
                </div>
            </x-panel>
        @endforeach

        <div class="sticky-actions">
            <button type="submit" class="btn btn-primary btn-xl w-100"><i class="bi bi-check2-circle"></i> Simpan Absensi</button>
        </div>
    </form>
@endif
@endsection
