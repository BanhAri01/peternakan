@extends('layouts.app')

@section('title', 'Simulasi Pembayaran')
@section('content-class', 'narrow')

@section('content')
<x-page-header title="Simulasi Pembayaran" subtitle="Halaman ini hanya ada di komputer lokal (mode uji). Di server sungguhan, pembeli diarahkan ke halaman pembayaran Midtrans." icon="bi-bug-fill" />

<x-panel>
    <div class="kv"><span class="k">Nomor tagihan</span><span class="v">{{ $payment->reference }}</span></div>
    <div class="kv"><span class="k">Paket</span><span class="v">{{ $payment->months }} bulan</span></div>
    <div class="kv total"><span class="k">Jumlah</span><span class="v">@rupiah($payment->amount)</span></div>

    <x-slot:footer>
        <div class="d-flex flex-wrap gap-2">
            <form action="{{ route('subscription.simulate.confirm', $payment->reference) }}" method="POST" class="flex-grow-1">
                @csrf
                <input type="hidden" name="status" value="paid">
                <button type="submit" class="btn btn-primary btn-xl w-100"><i class="bi bi-check2-circle"></i> Bayar berhasil</button>
            </form>
            <form action="{{ route('subscription.simulate.confirm', $payment->reference) }}" method="POST">
                @csrf
                <input type="hidden" name="status" value="failed">
                <button type="submit" class="btn btn-light btn-xl"><i class="bi bi-x-circle"></i> Gagal</button>
            </form>
        </div>
    </x-slot:footer>
</x-panel>
@endsection
