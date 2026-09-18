@extends('layouts.app') {{-- Sesuaikan dengan master layout Anda --}}

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Data Kandang (Coops)</h2>
        <a href="{{ route('coops.create') }}" class="btn btn-primary">+ Tambah Kandang</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nama</th>
                        <th>Strain</th>
                        <th>Kapasitas</th>
                        <th>Populasi Awal</th>
                        <th>Populasi Saat Ini</th>
                        <th>Tgl Chick-In</th>
                        <th>Umur Awal</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coops as $coop)
                        <tr>
                            <td class="fw-bold">{{ $coop->name }}</td>
                            <td>{{ $coop->strain ?? '-' }}</td>
                            <td>{{ number_format($coop->capacity) }}</td>
                            <td>{{ number_format($coop->initial_population) }}</td>
                            <td><span class="badge bg-info text-dark">{{ number_format($coop->current_population) }}</span></td>
                            <td>{{ $coop->chick_in_date->format('d M Y') }}</td>
                            <td>{{ $coop->initial_age_weeks }} mg</td>
                            <td>
                                @if($coop->status === 'active')
                                    <span class="badge bg-success">Active</span>
                                @elseif($coop->status === 'empty')
                                    <span class="badge bg-secondary">Empty</span>
                                @else
                                    <span class="badge bg-danger">Culled</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('coops.edit', $coop->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                <form action="{{ route('coops.destroy', $coop->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus kandang ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">Belum ada data kandang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">
        {{ $coops->links() }}
    </div>
</div>
@endsection