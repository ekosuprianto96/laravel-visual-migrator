<?php

namespace Ekosuprianto96\VisualMigrator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class CleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visual-migrator:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove orphaned visual metadata for tables that no longer exist in migrations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!Schema::hasTable('visual_migrator_entries')) {
            $this->error('Metadata table [visual_migrator_entries] not found. Please run migrations first.');
            return 1;
        }

        $this->info('Scanning migrations for existing tables...');

        // 1. Get all table names from existing migrations
        $migrationFiles = File::files(database_path('migrations'));
        $existingTables = [];

        foreach ($migrationFiles as $file) {
            $content = File::get($file->getPathname());
            if (preg_match_all("/Schema::(create|table)\(['\"](.+?)['\"]/i", $content, $matches)) {
                foreach ($matches[2] as $tableName) {
                    $existingTables[] = $tableName;
                }
            }
        }

        $existingTables = array_unique($existingTables);

        // 2. Get all metadata entries from DB
        $entries = DB::table('visual_migrator_entries')
            ->where('type', 'layout')
            ->get();

        $deletedCount = 0;

        foreach ($entries as $entry) {
            if (!in_array($entry->key, $existingTables)) {
                DB::table('visual_migrator_entries')->where('id', $entry->id)->delete();
                $this->warn("Removed orphaned metadata for table: [{$entry->key}]");
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("Successfully removed {$deletedCount} orphaned metadata entries.");
        } else {
            $this->info('No orphaned metadata found. Your database is clean.');
        }

        return 0;
    }
}
