<?php

namespace App\Assistant;

use App\Support\Format;
use Carbon\Carbon;

/**
 * Mesin demo Asisten Kandang (tanpa API key, tanpa biaya).
 *
 * Meniru cara kerja Claude: memilih alat dari kata kunci pertanyaan, lalu merangkum
 * hasil alat yang ASLI (query ke database) dengan kalimat sederhana. Dipakai untuk
 * demo & uji coba lokal: HEFAM_AI_DRIVER=demo. Pemakaian token diperkirakan dari
 * panjang teks agar "Pulsa Asisten" tetap bergerak seperti aslinya.
 */
class DemoLlm implements Llm
{
    public function available(): bool
    {
        return true;
    }

    public function send(string $model, string $effort, int $maxTokens, array $system, array $tools, array $messages): LlmReply
    {
        $last     = end($messages);
        $question = $this->lastQuestion($messages);
        $input    = (int) ceil(strlen(json_encode([$system, $tools, $messages], JSON_UNESCAPED_UNICODE)) / 4);

        // Putaran 1: pilih alat
        if (is_string($last['content'])) {
            $calls = [];
            foreach ($this->plan($question) as $i => [$name, $args]) {
                $calls[] = ['id' => 'demo_' . ($i + 1), 'name' => $name, 'input' => $args];
            }

            return new LlmReply(
                stopReason: 'tool_use',
                text: '',
                toolCalls: $calls,
                content: array_map(fn ($c) => ['type' => 'tool_use', 'id' => $c['id'], 'name' => $c['name'], 'input' => $c['input'] ?: (object) []], $calls),
                usage: ['input' => $input, 'output' => 40 * count($calls), 'cache_read' => 0, 'cache_write' => 0],
                model: $model,
            );
        }

        // Putaran 2: rangkum hasil alat
        $text = $this->compose($question, $this->results($messages));

        return new LlmReply(
            stopReason: 'end_turn',
            text: $text,
            toolCalls: [],
            content: [['type' => 'text', 'text' => $text]],
            usage: ['input' => $input, 'output' => (int) ceil(strlen($text) / 3), 'cache_read' => 0, 'cache_write' => 0],
            model: $model,
        );
    }

    // ------------------------------------------------------------------
    // Memilih alat
    // ------------------------------------------------------------------

    private function plan(string $question): array
    {
        $q    = mb_strtolower($question);
        $has  = fn (string ...$words) => collect($words)->contains(fn ($w) => str_contains($q, $w));
        $coop = preg_match('/kandang\s+([\pL\pN]+)/u', $q, $m) ? ['kandang' => $m[1]] : [];

        return match (true) {
            $has('rapor', 'laporan bulan')                           => [['produksi', ['dari' => Carbon::today()->startOfMonth()->toDateString()]], ['keuangan', []], ['piutang', []]],
            $has('afkir', 'diganti', 'ganti ayam', 'sudah tua')      => [['produksi', $coop + ['dari' => Carbon::today()->subDays(29)->toDateString()]]],
            $has('kenapa', 'mengapa', 'turun', 'sedikit', 'anjlok')  => [['produksi_harian', $coop + ['hari' => 10]], ['kesehatan', []]],
            $has('bayar', 'utang', 'hutang', 'piutang', 'tagih')     => [['piutang', []]],
            $has('untung', 'laba', 'rugi', 'keuangan', 'pengeluaran') => [['keuangan', []], ['keuangan', $this->lastMonth()]],
            $has('stok telur', 'sisa telur', 'telur di gudang')      => [['stok_telur', []]],
            $has('pakan', 'karung', 'gudang')                        => [['stok_pakan', []]],
            $has('pekerja', 'gaji', 'absen', 'rajin', 'karyawan')    => [['pekerja', []]],
            $has('vaksin', 'obat', 'vitamin', 'sakit', 'kesehatan')  => [['kesehatan', []]],
            $has('jual', 'pembeli', 'pelanggan', 'omzet')            => [['penjualan', []]],
            $has('telur', 'panen', 'produksi')                       => [['produksi_harian', $coop + ['hari' => 1]]],
            default                                                  => [['info_peternakan', []]],
        };
    }

    private function lastMonth(): array
    {
        $start = Carbon::today()->subMonthNoOverflow()->startOfMonth();

        return ['dari' => $start->toDateString(), 'sampai' => $start->copy()->endOfMonth()->toDateString()];
    }

    // ------------------------------------------------------------------
    // Merangkum hasil alat
    // ------------------------------------------------------------------

    private function compose(string $question, array $results): string
    {
        $q     = mb_strtolower($question);
        $parts = [];

        foreach ($results as $i => [$name, $data, $isError]) {
            if ($isError) {
                $parts[] = 'Maaf, ' . lcfirst(is_string($data) ? $data : 'data belum bisa dibaca.');
                continue;
            }

            $parts[] = match ($name) {
                'produksi_harian' => str_contains($q, 'kenapa') || str_contains($q, 'mengapa') || str_contains($q, 'turun') || str_contains($q, 'sedikit') || str_contains($q, 'anjlok')
                    ? $this->dropAnalysis($data, $results)
                    : $this->eggsToday($data),
                'produksi'        => str_contains($q, 'rapor') || str_contains($q, 'laporan') ? $this->productionReport($data) : $this->cullAdvice($data),
                'stok_pakan'      => $this->feed($data, str_contains($q, 'boros') || str_contains($q, 'hilang') || str_contains($q, 'selisih')),
                'keuangan'        => $i === 0 || $results[0][0] !== 'keuangan' ? $this->finance($data, $results[$i + 1][1] ?? null) : null,
                'piutang'         => $this->debts($data),
                'pekerja'         => $this->workers($data),
                'kesehatan'       => $this->health($data, $name === $results[0][0]),
                'penjualan'       => $this->sales($data),
                'stok_telur'      => $this->eggStock($data),
                default           => $this->farmInfo($data),
            };
        }

        return trim(implode("\n\n", array_filter($parts)));
    }

    private function eggsToday(array $data): string
    {
        $rows = collect($data['kandang'] ?? [])->map(fn ($k) => ['nama' => $k['kandang'], 'hari' => end($k['harian']) ?: null])->filter(fn ($r) => $r['hari']);

        if ($rows->isEmpty()) {
            return '🥚 Panen hari ini belum dicatat di aplikasi.';
        }

        return '🥚 Telur hari ini *' . Format::number($rows->sum(fn ($r) => $r['hari']['butir'])) . " butir*\n"
            . $rows->map(fn ($r) => '• ' . $r['nama'] . ': ' . Format::number($r['hari']['butir']) . ' butir (' . $this->per100($r['hari']['hdp']) . ' per 100 ekor)')->implode("\n");
    }

    private function dropAnalysis(array $data, array $results): string
    {
        $worst = null;

        foreach ($data['kandang'] ?? [] as $coop) {
            $days = collect($coop['harian']);
            if ($days->count() < 5) {
                continue;
            }

            $recent = $days->slice(-3);
            $before = $days->slice(0, -3);
            $drop   = $before->avg('hdp') - $recent->avg('hdp');

            if ($worst === null || $drop > $worst['drop']) {
                $worst = [
                    'nama'        => $coop['kandang'],
                    'drop'        => $drop,
                    'sebelum'     => $before->avg('hdp'),
                    'sekarang'    => $recent->avg('hdp'),
                    'standar'     => $recent->avg('standar_hdp'),
                    'pakan_dulu'  => $before->avg('pakan_kg'),
                    'pakan_kini'  => $recent->avg('pakan_kg'),
                    'mati'        => $recent->sum('mati'),
                    'hari_bawah'  => $recent->filter(fn ($d) => $d['hdp'] < $d['standar_hdp'] - 3)->count(),
                ];
            }
        }

        if ($worst === null) {
            return 'Catatan panen belum cukup untuk melihat naik-turunnya telur. Catat panen setiap hari ya, nanti saya bisa bandingkan.';
        }

        if ($worst['drop'] < 3) {
            return '👍 Produksi telur masih stabil. ' . $worst['nama'] . ' rata-rata *' . $this->per100($worst['sekarang']) . ' butir per 100 ekor*, standarnya ' . $this->per100($worst['standar']) . '.';
        }

        $feedChange = $worst['pakan_dulu'] > 0 ? ($worst['pakan_kini'] - $worst['pakan_dulu']) / $worst['pakan_dulu'] * 100 : 0;
        $feedLine   = $feedChange <= -5
            ? 'Pakan yang dimakan juga *berkurang* (dari ' . Format::number($worst['pakan_dulu']) . ' kg jadi ' . Format::number($worst['pakan_kini']) . ' kg sehari), tanda nafsu makan ayam turun.'
            : 'Pakannya normal (sekitar ' . Format::number($worst['pakan_kini']) . ' kg sehari).';

        $lines = [
            '📉 *' . $worst['nama'] . '* sudah ' . max(1, $worst['hari_bawah']) . ' hari bertelur di bawah biasanya: turun dari *' . $this->per100($worst['sebelum']) . '* jadi *' . $this->per100($worst['sekarang']) . ' butir per 100 ekor* (standarnya ' . $this->per100($worst['standar']) . ').',
            $feedLine,
            $worst['mati'] > 0 ? 'Ayam mati 3 hari terakhir: *' . Format::number($worst['mati']) . ' ekor*.' : 'Tidak ada ayam mati 3 hari terakhir.',
            '',
            'Saran:',
            '• Cek apakah ada ayam yang lesu, diare, atau jengger pucat.',
            '• Cek air minum dan pakan (tempat minum mampet, pakan apek).',
        ];

        $health = collect($results)->firstWhere(0, 'kesehatan')[1] ?? null;
        if (is_array($health) && ($v = collect($health['vaksin_30_hari_terakhir'] ?? [])->firstWhere('kandang', $worst['nama']))) {
            $lines[] = '• Vaksin terakhir di kandang ini ' . $this->day($v['tanggal']) . ' (' . $v['vaksin'] . ').';
        }
        $lines[] = 'Kalau berlanjut 2 hari lagi, panggil dokter hewan atau mantri.';

        return implode("\n", $lines);
    }

    private function cullAdvice(array $data): string
    {
        $rows = collect($data['kandang'] ?? []);
        if ($rows->isEmpty()) {
            return 'Belum ada catatan produksi 30 hari terakhir.';
        }

        $lines = ['🐔 Kondisi ayam 30 hari terakhir:'];
        foreach ($rows as $r) {
            $gap   = $r['standar_hdp'] !== null ? $r['rata_hdp'] - $r['standar_hdp'] : 0;
            $state = $r['rata_hdp'] < 70 ? '⚠️ sudah rendah' : ($gap < -5 ? 'di bawah standar' : 'masih bagus');
            $lines[] = '• ' . $r['kandang'] . ': *' . $this->per100($r['rata_hdp']) . ' butir per 100 ekor*'
                . ($r['standar_hdp'] !== null ? ' (standar ' . $this->per100($r['standar_hdp']) . ')' : '')
                . ', pakan ' . ($r['fcr'] ? str_replace('.', ',', (string) $r['fcr']) . ' kg per kg telur' : '-') . ', ' . $state;
        }

        $worst = $rows->sortBy('rata_hdp')->first();
        $lines[] = '';
        $lines[] = $worst['rata_hdp'] < 72 || ($worst['fcr'] ?? 0) > 2.6
            ? '*' . $worst['kandang'] . '* sudah waktunya dipikirkan untuk diganti (afkir): telurnya rendah sehingga hasil telur hampir habis untuk biaya pakan. Sebaiknya siapkan ayam pengganti dulu.'
            : 'Belum ada kandang yang perlu diafkir. Semua masih menghasilkan dengan baik.';

        return implode("\n", $lines);
    }

    private function productionReport(array $data): string
    {
        $rows = collect($data['kandang'] ?? []);
        if ($rows->isEmpty()) {
            return 'Belum ada catatan produksi bulan ini.';
        }

        $best  = $rows->sortByDesc('rata_hdp')->first();
        $worst = $rows->sortBy('rata_hdp')->first();

        return '📋 *Rapor bulan ini*' . "\n"
            . '• Total telur: *' . Format::number($data['total']['butir']) . ' butir* (' . Format::number($data['total']['kg']) . " kg)\n"
            . '• Kandang terbaik: ' . $best['kandang'] . ' (' . $this->per100($best['rata_hdp']) . " per 100 ekor)\n"
            . '• Kandang terendah: ' . $worst['kandang'] . ' (' . $this->per100($worst['rata_hdp']) . " per 100 ekor)\n"
            . '• Ayam mati/afkir: ' . Format::number($data['total']['mati_afkir']) . ' ekor';
    }

    private function feed(array $data, bool $focusGap): string
    {
        $feeds = collect($data['pakan'] ?? []);
        if ($feeds->isEmpty()) {
            return '📦 Belum ada data pakan di gudang.';
        }

        $gaps = collect($data['selisih_gudang_14_hari'] ?? [])->filter(fn ($g) => $g['selisih'] < 0);
        $sack = (float) ($data['berat_karung_kg'] ?? 50) ?: 50;
        $lines = [];

        if ($focusGap || $gaps->isNotEmpty()) {
            if ($gaps->isEmpty()) {
                $lines[] = '✅ Hitung gudang 2 minggu terakhir cocok dengan catatan. Tidak ada pakan yang hilang.';
            } else {
                $short = abs($gaps->sum('selisih'));
                $lines[] = '⚠️ Pakan di gudang *kurang ' . Format::number($short) . ' kg* (±' . Format::number($short / $sack, 1) . ' karung) dari catatan:';
                foreach ($gaps as $g) {
                    $lines[] = '• ' . $this->day($g['tanggal']) . ', ' . $g['pakan'] . ': tercatat ' . Format::number($g['tercatat']) . ' kg, dihitung ' . Format::number($g['dihitung']) . ' kg';
                }
                $lines[] = 'Mungkin tumpah, dimakan tikus, atau ada pemberian pakan yang belum dicatat. Coba ditanyakan ke pekerja.';
            }
            $lines[] = '';
        }

        $lines[] = '📦 Stok pakan:';
        foreach ($feeds as $f) {
            $lines[] = '• ' . $f['nama'] . ': *' . Format::number($f['stok_kg']) . ' kg* (±' . Format::number($f['stok_karung'], 1) . ' karung)'
                . ($f['cukup_hari'] !== null ? ', cukup ±' . $f['cukup_hari'] . ' hari' : '');
        }

        $soonest = $feeds->whereNotNull('cukup_hari')->sortBy('cukup_hari')->first();
        if ($soonest) {
            $runOut = Carbon::today()->addDays($soonest['cukup_hari']);
            $order  = Carbon::today()->addDays(max(0, $soonest['cukup_hari'] - 3));
            $lines[] = $soonest['cukup_hari'] <= 7
                ? '👉 ' . $soonest['nama'] . ' habis kira-kira ' . $this->day($runOut) . '. Sebaiknya pesan paling lambat *' . $this->day($order) . '*.'
                : '👍 Pakan masih aman lebih dari seminggu.';
        }

        return implode("\n", $lines);
    }

    private function finance(array $data, ?array $previous): string
    {
        $word  = $data['laba_bersih'] >= 0 ? 'untung' : 'rugi';
        $lines = ['📊 Bulan ini ' . $word . ' *' . Format::rupiah(abs($data['laba_bersih'])) . '*'];

        if (is_array($previous) && isset($previous['laba_bersih'])) {
            $diff = $data['laba_bersih'] - $previous['laba_bersih'];
            $lines[0] .= ', ' . ($diff >= 0 ? 'lebih baik ' : 'lebih rendah ') . Format::rupiah(abs($diff)) . ' dari bulan lalu (' . Format::rupiah($previous['laba_bersih']) . ').';
        }

        $costs = collect(['Pakan' => $data['pakan_dimakan'], 'Kas/gaji/listrik' => $data['pengeluaran_kas'], 'Kulakan telur' => $data['kulakan_telur'], 'Vaksin & obat' => $data['vaksin'] + $data['obat_dipakai']])
            ->filter()->sortDesc();

        $lines[] = '• Pendapatan: ' . Format::rupiah($data['total_pendapatan']);
        $lines[] = '• Biaya: ' . Format::rupiah($data['total_biaya']);
        if ($costs->isNotEmpty()) {
            $lines[] = '• Pengeluaran terbesar tetap *' . strtolower($costs->keys()->first()) . '* (' . Format::rupiah($costs->first()) . ').';
        }
        if (($data['piutang_baru'] ?? 0) > 0) {
            $lines[] = '• Masih ada ' . Format::rupiah($data['piutang_baru']) . ' penjualan yang belum dibayar.';
        }

        return implode("\n", $lines);
    }

    private function debts(array $data): string
    {
        $rows = collect($data['pelanggan'] ?? []);
        if ($rows->isEmpty()) {
            return '💰 Semua pembeli sudah lunas. 👍';
        }

        $lines = ['💰 Ada *' . $data['jumlah_pelanggan'] . ' pembeli* belum bayar, total *' . Format::rupiah($data['total_piutang']) . '*:'];
        foreach ($rows->take(5) as $r) {
            $age = Carbon::parse($r['nota_tertua'])->diffInDays(Carbon::today());
            $lines[] = '• ' . $r['nama'] . ' ' . Format::rupiah($r['piutang']) . ($age >= 7 ? ' (sudah ' . ($age >= 14 ? intdiv((int) $age, 7) . ' minggu' : (int) $age . ' hari') . ')' : '');
        }
        if ($data['sudah_jatuh_tempo'] > 0) {
            $lines[] = 'Yang sudah jatuh tempo: *' . Format::rupiah($data['sudah_jatuh_tempo']) . '*.';
        }

        return implode("\n", $lines);
    }

    private function workers(array $data): string
    {
        $rows = collect($data['pekerja'] ?? []);
        if ($rows->isEmpty()) {
            return '👷 Belum ada pekerja yang terdaftar.';
        }

        return '👷 Pekerja ' . $data['bulan'] . ":\n" . $rows->map(fn ($r) => '• ' . $r['nama'] . ' masuk *' . Format::number($r['hari_kerja'], 1) . ' hari*'
            . ($r['perkiraan_gaji'] > 0 ? ', gaji ' . Format::rupiah($r['perkiraan_gaji']) . ($r['sudah_dibayar'] ? ' (sudah dibayar)' : ' (belum dibayar)') : ''))->implode("\n");
    }

    private function health(array $data, bool $primary): ?string
    {
        if (!$primary) {
            return null; // dipakai sebagai bahan analisis saja
        }

        $lines = [];
        foreach ($data['vaksin_akan_datang'] ?? [] as $v) {
            $lines[] = '💉 Vaksin ' . $v['vaksin'] . ' ' . $v['kandang'] . ' jatuh *' . $this->day($v['tanggal']) . '*.';
        }
        if ($lines === []) {
            $lines[] = '💉 Tidak ada jadwal vaksin 2 minggu ke depan.';
        }
        foreach ($data['obat_perlu_perhatian'] ?? [] as $m) {
            $lines[] = '💊 ' . $m['nama'] . ' sisa *' . $m['stok'] . '*' . ($m['kedaluwarsa'] ? ' (' . $m['kedaluwarsa'] . ')' : ', segera beli lagi.');
        }

        return implode("\n", $lines);
    }

    private function sales(array $data): string
    {
        $top = collect($data['pelanggan_terbesar'] ?? [])->first();

        return '🧺 Penjualan bulan ini *' . Format::number($data['total_kg']) . ' kg*, senilai *' . Format::rupiah($data['total_rupiah']) . '*.'
            . ($top ? "\nPembeli terbesar: " . $top['nama'] . ' (' . Format::number($top['kg']) . ' kg).' : '');
    }

    private function eggStock(array $data): string
    {
        $lines = ['🥚 Stok telur di gudang:'];
        foreach ($data['jenis'] ?? [] as $g) {
            if (!$g['campur'] && $g['stok_kg'] > 0) {
                $lines[] = '• ' . $g['nama'] . ': ' . Format::number($g['stok_kg'], 1) . ' kg';
            }
        }
        if (($data['telur_campur_belum_disortir_butir'] ?? 0) > 0) {
            $lines[] = '• Belum disortir: ' . Format::number($data['telur_campur_belum_disortir_butir']) . ' butir';
        }

        return count($lines) > 1 ? implode("\n", $lines) : '🥚 Stok telur di gudang sedang kosong.';
    }

    private function farmInfo(array $data): string
    {
        $coops = collect($data['kandang'] ?? [])->where('populasi', '>', 0);

        return 'Saya Asisten Kandang *' . ($data['peternakan'] ?? 'peternakan Anda') . '*. Saat ini ada ' . $coops->count() . ' kandang aktif, total *'
            . Format::number($coops->sum('populasi')) . " ekor*.\n\nCoba tanya: telur hari ini, stok pakan, untung bulan ini, siapa yang belum bayar, atau kenapa telur turun.";
    }

    // ------------------------------------------------------------------

    private function lastQuestion(array $messages): string
    {
        foreach (array_reverse($messages) as $m) {
            if ($m['role'] === 'user' && is_string($m['content'])) {
                return $m['content'];
            }
        }

        return '';
    }

    /** [[nama alat, data, error?], ...] dari putaran alat terakhir. */
    private function results(array $messages): array
    {
        $names = [];
        foreach ($messages as $m) {
            if ($m['role'] === 'assistant' && is_array($m['content'])) {
                foreach ($m['content'] as $block) {
                    if (($block['type'] ?? null) === 'tool_use') {
                        $names[$block['id']] = $block['name'];
                    }
                }
            }
        }

        $out = [];
        foreach ((array) end($messages)['content'] as $block) {
            $error = (bool) ($block['isError'] ?? false);
            $out[] = [$names[$block['toolUseID']] ?? '?', $error ? $block['content'] : json_decode($block['content'], true), $error];
        }

        return $out;
    }

    private function per100(float|int|null $hdp): string
    {
        return Format::number((float) $hdp);
    }

    private function day(Carbon|string $date): string
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        return match (true) {
            $date->isToday()    => 'hari ini',
            $date->isTomorrow() => 'besok',
            default             => $date->translatedFormat('l, j F'),
        };
    }
}
