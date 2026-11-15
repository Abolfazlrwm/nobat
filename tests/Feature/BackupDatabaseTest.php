<?php

namespace Tests\Feature;

use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDO;
use Tests\TestCase;

/**
 * A backup you have never restored from is a hope, not a backup — so the
 * tests here open the produced file with a fresh PDO connection and check
 * the data inside, exactly like a restore would.
 */
class BackupDatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_consistent_backup_of_the_live_database(): void
    {
        $dir = storage_path('app/backups');
        $this->clearBackups($dir);

        Business::query()->create([
            'name' => 'کافیه روشن',
            'slug' => 'roshan',
            'phone' => '09121110000',
        ]);

        $this->artisan('nobat:backup')->assertSuccessful();

        $backups = glob($dir.'/nobat-*.sqlite') ?: [];
        $this->assertCount(1, $backups);

        $pdo = new PDO('sqlite:'.$backups[0]);
        $this->assertSame('ok', $pdo->query('PRAGMA integrity_check')->fetchColumn());
        $this->assertSame(1, (int) $pdo->query('SELECT COUNT(*) FROM businesses')->fetchColumn());
        $this->assertSame('کافیه روشن', $pdo->query('SELECT name FROM businesses LIMIT 1')->fetchColumn());
    }

    public function test_keep_option_prunes_oldest_backups(): void
    {
        $dir = storage_path('app/backups');
        $this->clearBackups($dir);
        touch($dir.'/nobat-20260101-000000-000000.sqlite');
        touch($dir.'/nobat-20260101-000000-000001.sqlite');

        $this->artisan('nobat:backup', ['--keep' => 1])->assertSuccessful();

        $remaining = glob($dir.'/nobat-*.sqlite') ?: [];
        $this->assertCount(1, $remaining);
        $this->assertStringContainsString(now()->format('Ymd'), basename($remaining[0]));
    }

    public function test_refuses_non_sqlite_drivers(): void
    {
        config(['database.default' => 'mysql']);

        $this->artisan('nobat:backup')
            ->assertFailed()
            ->expectsOutputToContain('nobat:backup supports SQLite only');

        // Restore before RefreshDatabase's teardown rolls back — it
        // re-resolves the default connection, and 'mysql' would try to
        // open a real server connection that no test environment has.
        config(['database.default' => 'sqlite']);
    }

    private function clearBackups(string $dir): void
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        foreach (glob($dir.'/nobat-*.sqlite') ?: [] as $file) {
            unlink($file);
        }
    }
}
