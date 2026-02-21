<?php

namespace Ekosuprianto96\VisualMigrator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class RefreshMetadataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visual-migrator:refresh {--force : Force the operation to run when in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh (clear) Visual Migrator layout metadata from database and JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->confirm('Are you sure you want to refresh all visual metadata? This will reset all table positions in the diagram.')) {
            
            // 1. Clear Database Table
            if (Schema::hasTable('visual_migrator_entries')) {
                DB::table('visual_migrator_entries')->truncate();
                $this->info('Database table [visual_migrator_entries] has been cleared.');
            }

            // 2. Clear JSON Fallback File
            $metaPath = database_path('migrations/.visual-migrator-meta.json');
            if (File::exists($metaPath)) {
                File::delete($metaPath);
                $this->info('JSON fallback file [.visual-migrator-meta.json] has been deleted.');
            }

            $this->info('Visual Migrator metadata has been successfully refreshed.');
        }

        return 0;
    }
}
