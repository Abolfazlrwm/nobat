<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
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

        if ($keep = (int) $this->option('keep')) {
            $this->prune($directory, $keep);
        }

        return self::SUCCESS;
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
