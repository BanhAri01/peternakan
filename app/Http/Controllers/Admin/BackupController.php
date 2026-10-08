<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackup;
use App\Support\Format;
use Illuminate\Support\Facades\Log;

class BackupController extends Controller
{
    public function __construct(private DatabaseBackup $backup) {}

    public function index()
    {
        return view('admin.backups.index', [
            'files'     => $this->backup->files(),
            'stale'     => $this->backup->isStale(),
            'directory' => $this->backup->directory(),
            'keepDays'  => (int) config('hefam.backup.keep_days'),
        ]);
    }

    public function store()
    {
        try {
            $result = $this->backup->run();
        } catch (\Throwable $e) {
            Log::error('Backup HEFAM manual gagal: ' . $e->getMessage());

            return back()->with('error', 'Backup gagal: ' . $e->getMessage());
        }

        return back()->with('success', 'Backup selesai: ' . $result['name'] . ' (' . Format::fileSize($result['size']) . ').');
    }

    public function download(string $file)
    {
        $path = $this->backup->find($file);

        abort_unless($path, 404);

        return response()->download($path, basename($path), ['Content-Type' => 'application/gzip']);
    }
}
