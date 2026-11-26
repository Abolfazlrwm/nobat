<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDO;

class BackupDatabase extends Command
{
    protected $signature = 'nobat:backup
                            {--path= : Where to write the backup (defaults to storage/app/backups)}
                            {--keep= : Keep only the N newest backup files after this run}';

    protected $description = 'Create a consistent SQLite backup file (VACUUM INTO) with an integrity check';

    public function handle(): int
    {
        // Read the driver from config, not from the connection instance:
        // resolving a connection would eagerly open one (a mysql swap in
        // tests would try to reach a real server just to check its name).
        if ((string) config('database.default') !== 'sqlite') {
            $this->error('nobat:backup supports SQLite only — use mysqldump for other drivers (docs/OPERATIONS.md, section 3).');

            return self::FAILURE;
        }

        $directory = (string) ($this->option('path') ?: storage_path('app/backups'));
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Microseconds keep the name unique: VACUUM INTO refuses to
        // overwrite an existing file, and a second run in the same second
        // would otherwise fail.
        $target = rtrim($directory, '/').'/nobat-'.now()->format('Ymd-His-u').'.sqlite';

        $db = DB::connection()->getPdo();

        // SQLite refuses to VACUUM inside a transaction (e.g. a wrapping
        // transaction from the caller or the test harness). Close any open
        // ones through the connection's own bookkeeping — in normal
        // operation there is none and this is a no-op.
        while (DB::connection()->transactionLevel() > 0) {
            DB::connection()->commit();
        }

        // VACUUM INTO yields a self-consistent snapshot even while the live
        // database is being written — a plain `cp` can copy mid-write pages
        // and produce a corrupt file that only reveals itself at restore time.
        $statement = $db->prepare('VACUUM INTO :path');
        $statement->bindValue(':path', $target);
        $statement->execute();

        $check = (new PDO('sqlite:'.$target))->query('PRAGMA integrity_check')->fetchColumn();
        if ($check !== 'ok') {
            $this->error("Backup written but integrity check failed: {$check} — do not trust this file.");

            return self::FAILURE;
        }

        $size = number_format((int) filesize($target) / 1024, 1);
        $this->info("Backup written: {$target} ({$size} KB, integrity ok)");

        // Retention is always on: the daily schedule passes no option, so a
        // config-less command would grow the backup directory forever until
        // the disk fills. --keep overrides the config default for one run.
        $keep = max(1, (int) ($this->option('keep') ?: config('nobat.backup.keep', 14)));
        $this->prune($directory, $keep);

        // A local backup dies with its disk. When a remote disk is configured,
        // every fresh snapshot is copied there too; a failed upload must fail
        // the command (and surface through the scheduler) — silently keeping
        // only the local copy is exactly how "we had backups" surprises happen.
        $disk = (string) config('nobat.backup.disk');
        if ($disk !== '' && ! $this->shipOffSite($disk, $target, $keep)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Stream the snapshot to the remote disk and prune the remote copies,
     * mirroring local retention. Returns false when the upload fails.
     */
    private function shipOffSite(string $disk, string $target, int $keep): bool
    {
        try {
            $store = Storage::disk($disk);
            $prefix = rtrim((string) config('nobat.backup.remote_prefix', 'backups'), '/');
            $remote = $prefix.'/'.basename($target);

            $stream = fopen($target, 'rb');
            if ($stream === false) {
                return false;
            }

            try {
                $store->writeStream($remote, $stream);
            } finally {
                fclose($stream);
            }

            $this->info("Off-site copy: [{$disk}] {$remote}");
            $this->pruneRemote($store, $prefix, $keep);
        } catch (\Throwable $e) {
            // report() routes to Sentry when configured, local log otherwise.
            report($e);
            $this->error("Local backup ok, but off-site copy failed: {$e->getMessage()}");

            return false;
        }

        return true;
    }

    /**
     * Same name-based retention as the local directory: snapshots sort by
     * their timestamp, so the oldest N beyond $keep are simply the first ones.
     */
    private function pruneRemote(Filesystem $store, string $prefix, int $keep): void
    {
        $remote = preg_grep('/nobat-.*\.sqlite$/', $store->files($prefix)) ?: [];
        sort($remote);

        foreach (array_slice($remote, 0, max(0, count($remote) - $keep)) as $path) {
            $store->delete($path);
            $this->line("Pruned remote {$path}");
        }
    }

    private function prune(string $directory, int $keep): void
    {
        $backups = glob($directory.'/nobat-*.sqlite') ?: [];
        if (count($backups) <= $keep) {
            $this->line('Nothing to prune.');

            return;
        }

        sort($backups);
        foreach (array_slice($backups, 0, count($backups) - $keep) as $file) {
            unlink($file);
            $this->line('Pruned '.basename($file));
        }
    }
}
