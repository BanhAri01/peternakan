<?php

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\DatabaseBackup;
use App\Services\FarmReminders;
use App\Services\WhatsAppHealth;
use App\Support\Format;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('hefam:backup {--connection=}', function (DatabaseBackup $backup) {
    try {
        $result = $backup->run($this->option('connection') ?: null);
    } catch (\Throwable $e) {
        Log::error('Backup Kandala gagal: ' . $e->getMessage());
        $this->error('Backup gagal: ' . $e->getMessage());

        return 1;
    }

    Log::info('Backup Kandala selesai: ' . $result['name']);
    $this->info('Backup selesai: ' . $result['path'] . ' (' . Format::fileSize($result['size']) . ')');

    if ($result['pruned'] > 0) {
        $this->line($result['pruned'] . ' backup lama dihapus.');
    }

    return 0;
})->purpose('Backup database Kandala ke file .sql.gz');

Artisan::command('hefam:pengingat {waktu : pagi atau sore} {--farm= : id peternakan} {--paksa : kirim ulang walau sudah terkirim hari ini}', function (FarmReminders $reminders) {
    $kind = $this->argument('waktu');

    if (!in_array($kind, FarmReminders::KINDS, true)) {
        $this->error('Waktu harus "pagi" atau "sore".');

        return 1;
    }

    $results = $reminders->sendAll($kind, $this->option('farm') ? (int) $this->option('farm') : null, (bool) $this->option('paksa'));

    foreach ($results as $farmId => $status) {
        $this->line('Peternakan #' . $farmId . ': ' . $status);
    }

    return 0;
})->purpose('Kirim pengingat WhatsApp pagi/sore ke pemilik peternakan');

Artisan::command('hefam:tagih-pelanggan {--farm= : id peternakan} {--paksa : kirim walau di luar jam 07:00-19:00 atau sudah diingatkan}', function (FarmReminders $reminders) {
    $force = (bool) $this->option('paksa');
    $results = $reminders->sendAllCustomerDueBills($this->option('farm') ? (int) $this->option('farm') : null, $force);

    foreach ($results as $farmId => $res) {
        $this->line('Peternakan #' . $farmId . ': ' . $res['status'] . ' (' . $res['sent'] . ' pesan ke pembeli)');
    }

    return 0;
})->purpose('Kirim tagihan WhatsApp otomatis langsung ke pelanggan yang utangnya jatuh tempo (pukul 07.00 - 19.00)');

Artisan::command('hefam:cek-wa', function (WhatsAppHealth $health) {
    foreach ($health->check() as $label => $status) {
        $this->line(WhatsAppHealth::LABELS[$label] . ': ' . $status);
    }

    return 0;
})->purpose('Cek apakah perangkat WhatsApp Fonnte masih tersambung; kirim email peringatan bila terputus');

Schedule::command('hefam:backup')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('hefam:cek-wa')->everyThirtyMinutes()->withoutOverlapping();
Schedule::command('model:prune', ['--model' => [ActivityLog::class]])->dailyAt('02:12');
Schedule::command('hefam:tagih-pelanggan')->hourly()->between('07:00', '19:00')->withoutOverlapping();
foreach (['pagi' => 'morning_at', 'sore' => 'evening_at'] as $kind => $key) {
    foreach (config('hefam.whatsapp.' . $key) as $time) {
        Schedule::command('hefam:pengingat ' . $kind)->dailyAt($time)->withoutOverlapping();
    }
}

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Membuat / mengatur ulang akun admin Kandala (pengelola semua peternakan)
Artisan::command('hefam:admin {email} {name=Admin Kandala}', function (string $email, string $name) {
    $password = Str::password(14, symbols: false);

    $user = User::updateOrCreate(
        ['email' => strtolower($email)],
        ['name' => $name, 'role' => 'superadmin', 'farm_id' => null, 'password' => $password]
    );

    $this->info('Akun admin Kandala siap: ' . $user->email);
    $this->line('Kata sandi baru: ' . $password);
    $this->comment('Simpan kata sandi ini di tempat aman, lalu masuk lewat halaman login.');
})->purpose('Buat atau atur ulang akun admin Kandala');

// Uji coba dan demo interaktif Asisten Kandang (AI) langsung dari terminal
Artisan::command('hefam:asisten {pertanyaan? : Pertanyaan tentang kandang} {--farm= : ID peternakan} {--mode= : Mode quick atau deep} {--driver= : Driver claude atau demo}', function () {
    $farm = \App\Models\Farm::find($this->option('farm'));

    if (!$farm) {
        $this->error('Isi --farm=ID peternakan.');
        return 1;
    }

    if ($driver = $this->option('driver')) {
        config(['hefam.assistant.driver' => $driver]);
    }

    $question = $this->argument('pertanyaan');
    if (!$question) {
        $question = $this->ask('Apa yang ingin Anda tanyakan ke Asisten Kandang?');
    }

    if (!$question) {
        $this->warn('Pertanyaan tidak boleh kosong.');
        return 1;
    }

    $owner = $farm->owner;
    $this->info("🤖 Menghubungi Asisten Kandang untuk: {$farm->name}...");
    $start = microtime(true);

    /** @var \App\Assistant\Agent $agent */
    $agent = app(\App\Assistant\Agent::class);
    $answer = $agent->ask($farm, $question, \App\Assistant\Agent::WEB, $owner, null, $this->option('mode'));
    $elapsed = round(microtime(true) - $start, 2);

    $this->newLine();
    $this->line("<fg=cyan;options=bold>Jawaban Asisten:</>");
    $this->line($answer->text);
    $this->newLine();

    $this->line("────────────────────────────────────────────────────────────");
    $this->line("Status       : " . $answer->status . " | Mode: " . ($answer->mode ?? 'auto'));
    $this->line("Alat Dipakai : " . ($answer->tools ? implode(', ', $answer->tools) : '-'));
    $this->line("Biaya Token  : Rp " . number_format($answer->cost, 2, ',', '.') . " | Waktu: {$elapsed}s");

    $quota = \App\Assistant\Quota::for($farm);
    $this->line("Sisa Pulsa   : {$quota->percentLeft()}% (±{$quota->questionsLeft()} pertanyaan lagi)");
    return 0;
})->purpose('Uji coba dan tanya langsung ke Asisten Kandang AI');

Artisan::command('hefam:batalkan-pending {farm : ID peternakan}', function () {
    $farm = \App\Models\Farm::find($this->argument('farm'));
    if (!$farm) {
        $this->error('Peternakan tidak ditemukan.');
        return 1;
    }

    $pending = \App\Models\SubscriptionPayment::withoutGlobalScopes()->where('farm_id', $farm->id)->where('status', 'pending');
    $count = $pending->count();
    if ($count === 0 || !$this->confirm("Batalkan {$count} tagihan langganan pending milik [{$farm->name}]? Pastikan pelanggan belum membayarnya.")) {
        $this->line('Tidak ada yang dibatalkan.');
        return 0;
    }

    $pending->update(['status' => 'expired']);
    $this->info("{$count} tagihan pending [{$farm->name}] dibatalkan.");
    return 0;
})->purpose('Batalkan tagihan langganan pending satu peternakan agar bisa membuat tagihan baru');

Artisan::command('hefam:asisten-pulsa {farm : ID peternakan} {rupiah : Tambahan pulsa bulan ini} {--catatan=}', function () {
    $farm = \App\Models\Farm::find($this->argument('farm'));
    $amount = (int) $this->argument('rupiah');
    if (!$farm || $amount <= 0 || $amount > 1000000) {
        $this->error('Peternakan tidak ditemukan atau nominal di luar 1 - 1.000.000.');
        return 1;
    }
    if (!$this->confirm("Tambah pulsa Asisten Rp{$amount} untuk [{$farm->name}] bulan ini?", true)) {
        return 0;
    }

    \App\Models\AssistantTopup::withoutGlobalScope(\App\Tenancy\FarmScope::class)->forceCreate([
        'farm_id' => $farm->id,
        'period'  => \Carbon\Carbon::today()->format('Y-m'),
        'amount'  => $amount,
        'note'    => $this->option('catatan') ?: 'Tambah pulsa oleh admin',
    ]);

    $quota = \App\Assistant\Quota::for($farm);
    $this->info("Pulsa [{$farm->name}] sekarang {$quota->percentLeft()}% (sisa Rp" . number_format($quota->remaining(), 0, ',', '.') . ').');
    return 0;
})->purpose('Tambah pulsa Asisten Kandang setelah peternakan membayar tambahan');
