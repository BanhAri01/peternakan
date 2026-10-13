@extends('layouts.app')

@section('title', 'Asisten Kandang')

@php use App\Support\Format; @endphp

@section('content')
<x-page-header title="Asisten Kandang (AI)" subtitle="Tanya apa saja tentang catatan kandang, pakan, produksi telur, piutang, dan kesehatan ayam lewat chat atau WhatsApp." icon="bi-robot" />

<x-alerts />

{{-- Ringkasan Status & Pulsa Asisten --}}
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <x-panel tone="brand" flush>
            <div class="p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-bold text-muted small"><i class="bi bi-battery-charging me-1"></i> Pulsa Asisten Bulan Ini</span>
                    <span class="badge {{ $quota['percent'] > 20 ? 'bg-success' : 'bg-warning text-dark' }} fs-6" id="quota-percent-badge">{{ $quota['percent'] }}%</span>
                </div>
                <div class="progress mb-2" style="height: 10px;">
                    <div id="quota-bar" class="progress-bar {{ $quota['percent'] > 20 ? 'bg-success' : 'bg-warning' }}" role="progressbar" style="width: {{ min(100, max(0, $quota['percent'])) }}%" aria-valuenow="{{ $quota['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span id="quota-questions-text">±{{ $quota['questions'] }} pertanyaan lagi</span>
                    <span id="quota-rupiah-text">{{ Format::rupiah($quota['remaining']) }} / {{ Format::rupiah($quota['budget']) }}</span>
                </div>
            </div>
        </x-panel>
    </div>

    <div class="col-md-4">
        <x-panel tone="egg" flush>
            <div class="p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-bold text-muted small"><i class="bi bi-cpu-fill me-1"></i> Mesin Kecerdasan (AI)</span>
                    <span class="badge {{ $driver === 'claude' && $live ? 'bg-primary' : 'bg-info text-dark' }}">
                        {{ $driver === 'claude' ? ($live ? 'Claude API (Live)' : 'Claude (Key Belum Diisi)') : 'Demo Cerdas (Offline)' }}
                    </span>
                </div>
                <div class="fw-bold fs-5 mb-1 text-truncate">
                    {{ $driver === 'claude' ? 'Anthropic Claude' : 'Simulasi Offline HEFAM' }}
                </div>
                <div class="small text-muted">
                    {{ $driver === 'claude' ? 'Tanya Cepat (Haiku) & Analisis (Sonnet)' : 'Membaca data riil database tanpa memotong biaya API' }}
                </div>
            </div>
        </x-panel>
    </div>

    <div class="col-md-4">
        <x-panel tone="neutral" flush>
            <div class="p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-bold text-muted small"><i class="bi bi-chat-dots-fill me-1"></i> Pemakaian Bulan Ini</span>
                    <span class="badge bg-secondary">{{ $usage->questions ?? 0 }} Ditanyakan</span>
                </div>
                <div class="fw-bold fs-5 mb-1">
                    {{ ($usage->wa_questions ?? 0) }} via WhatsApp
                </div>
                <div class="small text-muted">
                    Biaya API terpakai: {{ Format::rupiah($usage->cost ?? 0) }}
                </div>
            </div>
        </x-panel>
    </div>
</div>

<div class="row g-4" x-data="assistantChat()">
    {{-- Kolom Chat Interaktif --}}
    <div class="col-lg-8">
        <x-panel title="Percakapan dengan Asisten" icon="bi-chat-left-text-fill">
            {{-- Daftar Bubble Chat --}}
            <div class="chat-container mb-3 p-3 rounded" style="background: var(--bg); min-height: 380px; max-height: 520px; overflow-y: auto; border: 1px solid var(--line);" id="chat-messages" x-ref="chatBox">
                @if($messages->isEmpty())
                    <div class="text-center text-muted py-5" x-show="chatHistory.length === 0">
                        <i class="bi bi-robot display-4 text-muted opacity-50 mb-3 d-block"></i>
                        <h6 class="fw-bold">Belum ada percakapan</h6>
                        <p class="small mb-3">Tanyakan apa saja tentang kandang, atau klik salah satu contoh pertanyaan di bawah.</p>
                    </div>
                @endif

                {{-- Riwayat dari Database --}}
                @foreach($messages as $msg)
                    <div class="d-flex mb-3 {{ $msg->role === 'user' ? 'justify-content-end' : 'justify-content-start' }}">
                        @if($msg->role !== 'user')
                            <div class="avatar-assistant me-2">
                                <span class="badge rounded-circle p-2 bg-success text-white"><i class="bi bi-robot"></i></span>
                            </div>
                        @endif
                        <div class="bubble p-3 rounded-3 shadow-sm {{ $msg->role === 'user' ? 'bg-primary text-white' : 'bg-white text-dark border' }}" style="max-width: 82%;">
                            <div class="message-text" style="white-space: pre-line; word-break: break-word;">{!! nl2br(e($msg->content)) !!}</div>
                            <div class="d-flex align-items-center justify-content-end gap-2 mt-1 small opacity-75">
                                @if($msg->role !== 'user' && $msg->mode)
                                    <span class="badge bg-light text-dark border">{{ $msg->mode === 'deep' ? 'Analisis' : 'Tanya Cepat' }}</span>
                                @endif
                                <span style="font-size: 0.75rem;">{{ $msg->created_at->format('H:i') }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Percakapan Baru (Client-Side Reactive) --}}
                <template x-for="(item, idx) in chatHistory" :key="idx">
                    <div class="d-flex mb-3" :class="item.role === 'user' ? 'justify-content-end' : 'justify-content-start'">
                        <template x-if="item.role !== 'user'">
                            <div class="avatar-assistant me-2">
                                <span class="badge rounded-circle p-2 bg-success text-white"><i class="bi bi-robot"></i></span>
                            </div>
                        </template>
                        <div class="bubble p-3 rounded-3 shadow-sm" :class="item.role === 'user' ? 'bg-primary text-white' : 'bg-white text-dark border'" style="max-width: 82%;">
                            <div class="message-text" style="white-space: pre-line; word-break: break-word;" x-text="item.text"></div>
                            <div class="d-flex align-items-center justify-content-end gap-2 mt-1 small opacity-75">
                                <template x-if="item.mode">
                                    <span class="badge bg-light text-dark border" x-text="item.mode === 'deep' ? 'Analisis' : 'Tanya Cepat'"></span>
                                </template>
                                <span style="font-size: 0.75rem;" x-text="item.time"></span>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Animasi Mengetik / Berpikir --}}
                <div class="d-flex mb-3 justify-content-start" x-show="loading" x-cloak>
                    <div class="avatar-assistant me-2">
                        <span class="badge rounded-circle p-2 bg-success text-white"><i class="bi bi-robot"></i></span>
                    </div>
                    <div class="bubble p-3 rounded-3 shadow-sm bg-white border text-muted">
                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                        <span x-text="loadingText">Sedang membaca buku catatan kandang...</span>
                    </div>
                </div>
            </div>

            {{-- Rekomendasi Pertanyaan Cepat --}}
            <div class="mb-3">
                <span class="small text-muted fw-bold d-block mb-2"><i class="bi bi-stars text-warning me-1"></i> Contoh pertanyaan cepat:</span>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($examples as $ex)
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill text-start" @click="askQuick('{{ addslashes($ex) }}')" :disabled="loading">
                            {{ $ex }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Form Input Pertanyaan --}}
            <form @submit.prevent="submitQuestion" class="mt-3">
                <div class="row g-2 align-items-center mb-2">
                    <div class="col-auto">
                        <label class="small text-muted fw-bold">Mode:</label>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm" x-model="mode" :disabled="loading">
                            <option value="auto">Otomatis (Deteksi Kebutuhan)</option>
                            <option value="quick">Tanya Cepat (Hemat Pulsa)</option>
                            <option value="deep">Analisis Mendalam (Penalaran Penuh)</option>
                        </select>
                    </div>
                </div>

                <div class="input-group">
                    <input type="text" class="form-control form-control-lg" placeholder="Ketik pertanyaan di sini (misal: pakan sisa berapa hari?)..." x-model="question" :disabled="loading" required maxlength="500" autofocus>
                    <button class="btn btn-primary btn-lg px-4" type="submit" :disabled="loading || !question.trim()">
                        <span x-show="!loading"><i class="bi bi-send-fill me-1"></i> Tanya</span>
                        <span x-show="loading"><span class="spinner-border spinner-border-sm me-1"></span> Menjawab</span>
                    </button>
                </div>
            </form>
        </x-panel>
    </div>

    {{-- Kolom WhatsApp & Pengaturan Nomor --}}
    <div class="col-lg-4">
        {{-- Integrasi WhatsApp --}}
        <x-panel title="Tanya Lewat WhatsApp" icon="bi-whatsapp" tone="brand" class="mb-4">
            <p class="small text-muted mb-3">
                Pemilik dan mandor bisa bertanya langsung ke WhatsApp Asisten tanpa perlu membuka aplikasi. Nomor HP harus didaftarkan di bawah ini agar sistem mengenali peternakan Anda.
            </p>

            {{-- Form Tambah Nomor --}}
            <form action="{{ route('assistant.phones.store') }}" method="POST" class="mb-3">
                @csrf
                <div class="mb-2">
                    <label class="form-label small fw-bold">Nomor WhatsApp</label>
                    <input type="tel" name="phone" class="form-control" placeholder="0812 3456 7890" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small fw-bold">Pemilik / Label (Opsional)</label>
                    <input type="text" name="label" class="form-control" placeholder="HP Bapak, Mandor Kandang">
                </div>
                <button type="submit" class="btn btn-sm btn-egg w-100"><i class="bi bi-plus-circle me-1"></i> Daftarkan Nomor</button>
            </form>

            <hr class="my-3">

            <h6 class="fw-bold small mb-2"><i class="bi bi-phone-fill me-1"></i> Nomor Terdaftar:</h6>
            @if($phones->isEmpty())
                <div class="text-muted small py-2 text-center">Belum ada nomor WA yang didaftarkan.</div>
            @else
                <ul class="list-group list-group-flush small">
                    @foreach($phones as $p)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <div>
                                <span class="fw-bold text-dark d-block">{{ $p->phone }}</span>
                                <span class="text-muted" style="font-size: 0.8rem;">{{ $p->label ?? ($p->user->name ?? 'Pemilik') }}</span>
                            </div>
                            <form action="{{ route('assistant.phones.destroy', $p) }}" method="POST" onsubmit="return confirm('Lepas nomor {{ $p->phone }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Hapus"><i class="bi bi-trash"></i></button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>

        {{-- Kartu Contekan Pertanyaan Siap Cetak --}}
        <x-panel title="Kartu Pertanyaan (Bisa Dicetak)" icon="bi-printer-fill">
            <div class="p-2 border rounded bg-white small mb-3">
                <div class="fw-bold text-center border-bottom pb-1 mb-2 text-success">📱 CONTEKAN ASISTEN KANDANG</div>
                <ul class="list-unstyled mb-2">
                    <li class="mb-1">🥚 <b>Telur hari ini berapa?</b></li>
                    <li class="mb-1">🌾 <b>Pakan cukup berapa hari?</b></li>
                    <li class="mb-1">💰 <b>Bulan ini untung berapa?</b></li>
                    <li class="mb-1">📝 <b>Siapa yang belum bayar?</b></li>
                    <li class="mb-1">❓ <b>Kenapa telur turun?</b></li>
                    <li class="mb-1">💉 <b>Kapan jadwal vaksin?</b></li>
                    <li class="mb-1">🔋 <b>Pulsa</b> (cek sisa kuota)</li>
                </ul>
                <div class="text-muted fst-italic" style="font-size: 0.75rem;">
                    *Asisten hanya membaca catatan, tidak mengubah data apa pun.
                </div>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="window.print()">
                <i class="bi bi-printer me-1"></i> Cetak Kartu untuk di Kandang
            </button>
        </x-panel>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('assistantChat', () => ({
        question: '',
        mode: 'auto',
        loading: false,
        loadingText: 'Sedang membaca buku catatan kandang...',
        chatHistory: [],

        askQuick(text) {
            this.question = text;
            this.submitQuestion();
        },

        async submitQuestion() {
            const q = this.question.trim();
            if (!q || this.loading) return;

            const now = new Date();
            const timeStr = ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2);

            // Tambahkan pertanyaan user ke layar
            this.chatHistory.push({
                role: 'user',
                text: q,
                time: timeStr
            });

            this.question = '';
            this.loading = true;
            this.loadingText = q.toLowerCase().includes('kenapa') || q.toLowerCase().includes('analisis')
                ? 'Sedang menganalisis tren panen & kesehatan kandang...'
                : 'Sedang membaca buku catatan kandang...';

            this.$nextTick(() => this.scrollToBottom());

            try {
                const response = await fetch('{{ route('assistant.ask') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        question: q,
                        mode: this.mode
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    this.chatHistory.push({
                        role: 'assistant',
                        text: data.text,
                        mode: data.mode,
                        time: data.time || timeStr
                    });

                    // Perbarui badge & info pulsa di layar
                    if (data.quota) {
                        const badge = document.getElementById('quota-percent-badge');
                        const bar = document.getElementById('quota-bar');
                        const qText = document.getElementById('quota-questions-text');
                        const rText = document.getElementById('quota-rupiah-text');

                        if (badge) badge.innerText = data.quota.percent + '%';
                        if (bar) {
                            bar.style.width = Math.min(100, Math.max(0, data.quota.percent)) + '%';
                            bar.setAttribute('aria-valuenow', data.quota.percent);
                        }
                        if (qText) qText.innerText = '±' + data.quota.questions + ' pertanyaan lagi';
                        if (rText) rText.innerText = 'Rp ' + Number(data.quota.remaining).toLocaleString('id-ID') + ' / Rp ' + Number(data.quota.budget).toLocaleString('id-ID');
                    }
                } else {
                    this.chatHistory.push({
                        role: 'assistant',
                        text: data.message || 'Maaf, terjadi kesalahan saat menghubungi asisten.',
                        time: timeStr
                    });
                }
            } catch (err) {
                this.chatHistory.push({
                    role: 'assistant',
                    text: 'Koneksi ke server terputus. Silakan coba beberapa saat lagi.',
                    time: timeStr
                });
            } finally {
                this.loading = false;
                this.$nextTick(() => this.scrollToBottom());
            }
        },

        scrollToBottom() {
            const el = this.$refs.chatBox;
            if (el) el.scrollTop = el.scrollHeight;
        }
    }));
});
</script>
@endpush
