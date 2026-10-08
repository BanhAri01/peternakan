<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DatabaseBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase, FarmTestHelpers;

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFarm();

        $this->dir = storage_path('framework/testing/backups-' . uniqid());
        config([
            'hefam.backup.path'          => $this->dir,
            'database.connections.dump'  => [
                'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 3306,
                'database' => 'hefam', 'username' => 'hefam', 'password' => 'rahasia',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        parent::tearDown();
    }

    private function fakeMysqldump(string $content = "CREATE TABLE farms;\n-- Dump completed on 2026-10-08\n"): void
    {
        Process::fake(function (PendingProcess $process) use ($content) {
            foreach ($process->command as $part) {
                if (str_starts_with($part, '--result-file=')) {
                    file_put_contents(substr($part, 14), $content);
                }
            }

            return Process::result();
        });
    }

    public function test_perintah_backup_membuat_file_gzip_yang_bisa_dibaca(): void
    {
        $this->fakeMysqldump();

        $this->artisan('hefam:backup', ['--connection' => 'dump'])->assertSuccessful();

        $files = File::glob($this->dir . '/hefam-*.sql.gz');
        $this->assertCount(1, $files);
        $this->assertStringContainsString('Dump completed', gzdecode(file_get_contents($files[0])));
        $this->assertEmpty(File::glob($this->dir . '/*.tmp'));

        Process::assertRan(function (PendingProcess $process) {
            return in_array('--single-transaction', $process->command, true)
                && ($process->environment['MYSQL_PWD'] ?? null) === 'rahasia'
                && !in_array('--password=rahasia', $process->command, true);
        });
    }

    public function test_backup_tidak_lengkap_dianggap_gagal(): void
    {
        $this->fakeMysqldump("CREATE TABLE farms;\n");

        $this->artisan('hefam:backup', ['--connection' => 'dump'])->assertFailed();

        $this->assertEmpty(File::glob($this->dir . '/*'));
    }

    public function test_backup_lama_dihapus_tetapi_minimal_tujuh_disimpan(): void
    {
        File::ensureDirectoryExists($this->dir);
        foreach (range(1, 10) as $i) {
            $path = $this->dir . '/hefam-2026-01-' . str_pad($i, 2, '0', STR_PAD_LEFT) . '-000000.sql.gz';
            file_put_contents($path, 'x');
            touch($path, now()->subDays(40 + $i)->getTimestamp());
        }

        $removed = app(DatabaseBackup::class)->prune();

        $this->assertSame(3, $removed);
        $this->assertCount(7, File::glob($this->dir . '/hefam-*.sql.gz'));
    }

    public function test_admin_melihat_dan_mengunduh_backup(): void
    {
        $this->fakeMysqldump();
        config(['hefam.backup.connection' => 'dump']);
        $admin = User::create(['name' => 'Ari', 'email' => 'admin@hefam.test', 'password' => 'admin12345', 'role' => 'superadmin']);

        $this->actingAs($admin)->get(route('admin.backups.index'))->assertOk()->assertSee('Belum ada file backup');
        $this->actingAs($admin)->post(route('admin.backups.store'))->assertSessionHas('success');

        $name = basename(File::glob($this->dir . '/hefam-*.sql.gz')[0]);
        $this->actingAs($admin)->get(route('admin.backups.index'))->assertOk()->assertSee($name)->assertSee('berjalan normal');
        $this->actingAs($admin)->get(route('admin.backups.download', $name))->assertOk()->assertDownload($name);
    }

    public function test_pemilik_tidak_bisa_membuka_atau_mengunduh_backup(): void
    {
        $this->actingAs($this->owner)->get(route('admin.backups.index'))->assertForbidden();
        $this->actingAs($this->owner)->get('/admin/backup/hefam-2026-01-01-000000.sql.gz')->assertForbidden();
    }

    public function test_nama_file_aneh_tidak_bisa_diunduh(): void
    {
        $admin = User::create(['name' => 'Ari', 'email' => 'admin@hefam.test', 'password' => 'admin12345', 'role' => 'superadmin']);

        $this->actingAs($admin)->get('/admin/backup/..%2F.env')->assertNotFound();
        $this->actingAs($admin)->get('/admin/backup/hefam-2026-01-01-000000.sql.gz')->assertNotFound();
    }
}
