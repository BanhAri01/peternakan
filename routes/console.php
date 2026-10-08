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

Schedule::command('hefam:backup')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('model:prune', ['--model' => [ActivityLog::class]])->dailyAt('02:15');
Schedule::command('hefam:pengingat pagi')->dailyAt(config('hefam.whatsapp.morning_at'))->withoutOverlapping();
Schedule::command('hefam:pengingat sore')->dailyAt(config('hefam.whatsapp.evening_at'))->withoutOverlapping();

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
