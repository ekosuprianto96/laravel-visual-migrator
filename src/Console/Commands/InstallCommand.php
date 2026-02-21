<?php

namespace Ekosuprianto96\VisualMigrator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visual-migrator:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Visual Migrator assets and configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Installing Visual Migrator...');

        // 1. Publish Configuration
        $this->publishConfig();

        // 2. Publish Assets
        $this->publishAssets();

        // 3. Run Migrations
        if ($this->confirm('Do you want to run the database migrations now?', true)) {
            $this->call('migrate');
        }

        $this->info('Visual Migrator installed successfully.');
        $this->comment('You can access the dashboard at: ' . config('app.url') . '/' . config('visual-migrator.path', 'visual-migrator'));

        return 0;
    }

    protected function publishConfig()
    {
        $this->info('Publishing configuration...');
        Artisan::call('vendor:publish', [
            '--tag' => 'visual-migrator-config',
            '--force' => false
        ]);
    }

    protected function publishAssets()
    {
        $this->info('Publishing assets...');
        Artisan::call('vendor:publish', [
            '--tag' => 'visual-migrator-assets',
            '--force' => true
        ]);
    }
}
