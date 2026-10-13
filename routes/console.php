<?php

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\DatabaseBackup;
use App\Services\FarmReminders;
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
        Log::error('Backup HEFAM gagal: ' . $e->getMessage());
        $this->error('Backup gagal: ' . $e->getMessage());

        return 1;
    }

    Log::info('Backup HEFAM selesai: ' . $result['name']);
    $this->info('Backup selesai: ' . $result['path'] . ' (' . Format::fileSize($result['size']) . ')');

    if ($result['pruned'] > 0) {
        $this->line($result['pruned'] . ' backup lama dihapus.');
    }

    return 0;
})->purpose('Backup database HEFAM ke file .sql.gz');

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

Schedule::command('hefam:backup')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('model:prune', ['--model' => [ActivityLog::class]])->dailyAt('02:15');
Schedule::command('hefam:tagih-pelanggan')->hourly()->between('07:00', '19:00')->withoutOverlapping();
foreach (['pagi' => 'morning_at', 'sore' => 'evening_at'] as $kind => $key) {
    foreach (config('hefam.whatsapp.' . $key) as $time) {
        Schedule::command('hefam:pengingat ' . $kind)->dailyAt($time)->withoutOverlapping();
    }
}

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Membuat / mengatur ulang akun admin HEFAM (pengelola semua peternakan)
Artisan::command('hefam:admin {email} {name=Admin HEFAM}', function (string $email, string $name) {
    $password = Str::password(14, symbols: false);

    $user = User::updateOrCreate(
        ['email' => strtolower($email)],
        ['name' => $name, 'role' => 'superadmin', 'farm_id' => null, 'password' => $password]
    );

    $this->info('Akun admin HEFAM siap: ' . $user->email);
    $this->line('Kata sandi baru: ' . $password);
    $this->comment('Simpan kata sandi ini di tempat aman, lalu masuk lewat halaman login.');
})->purpose('Buat atau atur ulang akun admin HEFAM');

// Uji coba dan demo interaktif Asisten Kandang (AI) langsung dari terminal
Artisan::command('hefam:asisten {pertanyaan? : Pertanyaan tentang kandang} {--farm= : ID peternakan} {--mode= : Mode quick atau deep} {--driver= : Driver claude atau demo}', function () {
    $farmId = $this->option('farm');
    $farm = $farmId ? \App\Models\Farm::find($farmId) : \App\Models\Farm::where('status', 'active')->first();

    if (!$farm) {
        $this->error('Tidak ada peternakan aktif.');
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

Artisan::command('hefam:batalkan-pending', function () {
    $count = \App\Models\SubscriptionPayment::withoutGlobalScopes()->where('status', 'pending')->update(['status' => 'expired']);
    $this->info("Berhasil membatalkan {$count} tagihan pending lama.");
    return 0;
})->purpose('Batalkan tagihan langganan pending agar bisa membuat tagihan baru');

