<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class DatabaseBackup
{
    public const PATTERN = 'hefam-*.sql.gz';

    private const CHUNK_BYTES = 1048576;

    public function directory(): string
    {
        return rtrim((string) config('hefam.backup.path'), '/\\');
    }

    public function run(?string $connection = null): array
    {
        $connection ??= config('hefam.backup.connection') ?: config('database.default');
        $config = config("database.connections.{$connection}");

        if (!$config) {
            throw new RuntimeException("Koneksi database [{$connection}] tidak ditemukan.");
        }

        File::ensureDirectoryExists($this->directory(), 0750);

        $name   = 'hefam-' . now()->format('Y-m-d-His') . '.sql.gz';
        $target = $this->directory() . DIRECTORY_SEPARATOR . $name;
        $raw    = $target . '.tmp';

        try {
            match ($config['driver']) {
                'mysql', 'mariadb' => $this->dumpMysql($config, $raw),
                'sqlite'           => $this->dumpSqlite($config, $raw),
                default            => throw new RuntimeException('Jenis database tidak didukung: ' . $config['driver']),
            };

            $this->compress($raw, $target);
        } catch (\Throwable $e) {
            File::delete($target);

            throw $e;
        } finally {
            File::delete($raw);
        }

        $pruned = $this->prune();

        return ['name' => $name, 'path' => $target, 'size' => filesize($target), 'pruned' => $pruned];
    }

    public function files(): Collection
    {
        return collect(File::glob($this->directory() . DIRECTORY_SEPARATOR . self::PATTERN))
            ->map(fn (string $path) => [
                'name' => basename($path),
                'path' => $path,
                'size' => filesize($path),
                'time' => Carbon::createFromTimestamp(filemtime($path)),
            ])
            ->sortByDesc('time')
            ->values();
    }

    public function find(string $name): ?string
    {
        $path = $this->directory() . DIRECTORY_SEPARATOR . basename($name);

        return fnmatch(self::PATTERN, basename($name)) && is_file($path) ? $path : null;
    }

    public function latest(): ?array
    {
        return $this->files()->first();
    }

    public function isStale(): bool
    {
        $latest = $this->latest();

        return !$latest || $latest['time']->lt(now()->subHours((int) config('hefam.backup.stale_hours')));
    }

    public function prune(): int
    {
        $limit   = now()->subDays((int) config('hefam.backup.keep_days'));
        $removed = 0;

        $this->files()
            ->slice((int) config('hefam.backup.keep_min'))
            ->filter(fn (array $file) => $file['time']->lt($limit))
            ->each(function (array $file) use (&$removed) {
                File::delete($file['path']);
                $removed++;
            });

        return $removed;
    }

    private function dumpMysql(array $config, string $raw): void
    {
        $command = [
            config('hefam.backup.mysqldump'),
            '--host=' . $config['host'],
            '--port=' . $config['port'],
            '--user=' . $config['username'],
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--no-tablespaces',
            '--default-character-set=utf8mb4',
            '--result-file=' . $raw,
        ];

        if (!empty($config['unix_socket'])) {
            $command[] = '--socket=' . $config['unix_socket'];
        }

        $command[] = $config['database'];

        $result = Process::env(['MYSQL_PWD' => (string) $config['password']])
            ->timeout(1800)
            ->run($command);

        if ($result->failed()) {
            throw new RuntimeException('mysqldump gagal: ' . trim($result->errorOutput() ?: $result->output()));
        }

        if (!is_file($raw) || !str_contains($this->tail($raw), 'Dump completed')) {
            throw new RuntimeException('File backup tidak lengkap (tanda "Dump completed" tidak ditemukan).');
        }
    }

    private function dumpSqlite(array $config, string $raw): void
    {
        $database = $config['database'];

        if ($database === ':memory:' || !is_file($database)) {
            throw new RuntimeException('File database SQLite tidak ditemukan.');
        }

        File::copy($database, $raw);
    }

    private function compress(string $raw, string $target): void
    {
        $in  = fopen($raw, 'rb');
        $out = gzopen($target, 'wb6');

        if (!$in || !$out) {
            throw new RuntimeException('Tidak bisa menulis file backup di ' . $this->directory());
        }

        try {
            while (!feof($in)) {
                gzwrite($out, fread($in, self::CHUNK_BYTES));
            }
        } finally {
            fclose($in);
            gzclose($out);
        }
    }

    private function tail(string $path, int $bytes = 512): string
    {
        $size   = filesize($path);
        $handle = fopen($path, 'rb');
        fseek($handle, max(0, $size - $bytes));
        $tail = (string) fread($handle, $bytes);
        fclose($handle);

        return $tail;
    }
}
