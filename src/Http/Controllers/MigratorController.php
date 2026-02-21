<?php

namespace Ekosuprianto96\VisualMigrator\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Ekosuprianto96\VisualMigrator\Services\MigrationGenerator;
use Ekosuprianto96\VisualMigrator\Services\MigrationParser;

class MigratorController extends Controller
{
    protected $generator;
    protected $parser;

    public function __construct(MigrationGenerator $generator, MigrationParser $parser)
    {
        $this->generator = $generator;
        $this->parser = $parser;
    }

    /**
     * Display the visual migrator dashboard.
     */
    public function index()
    {
        return view('visual-migrator::dashboard');
    }

    /**
     * Get the current schema from Laravel migrations.
     */
    public function getSchema(Request $request)
    {
        try {
            // Check for Lightweight hash check for polling
            if ($request->has('check_hash')) {
                $files = \Illuminate\Support\Facades\File::files(database_path('migrations'));
                $hash = md5(implode('', array_map(fn($f) => $f->getFilename(), $files)));
                return response()->json(['hash' => $hash]);
            }

            $schema = $this->parser->parse();

            // Auto-sync source code to standalone project if enabled (Dev only)
            $standalonePath = env('VISUAL_MIGRATOR_STANDALONE_PATH');
            if ($standalonePath && env('VISUAL_MIGRATOR_SYNC_SOURCE', false)) {
                $this->generator->syncPackageSource($standalonePath);
            }

            return response()->json($schema);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse migrations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync the visual schema changes back to Laravel migrations.
     */
    public function syncSchema(Request $request)
    {
        $schema = $request->all();
        
        try {
            $files = $this->generator->generate($schema);
            
            return response()->json([
                'success' => true,
                'message' => count($files) . ' migrations generated successfully.',
                'files' => $files
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate migrations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save only the visual layout (node positions) metadata.
     */
    public function saveLayout(Request $request)
    {
        $schema = $request->all();
        
        try {
            $this->generator->saveLayoutMeta($schema['collections'] ?? []);
            
            return response()->json([
                'success' => true,
                'message' => 'Layout positions saved successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save layout: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Fetch schema from a live database connection.
     */
    public function getLiveDbSchema($databaseId)
    {
        // Logic to use Schema::getColumnListing() etc.
        return response()->json([
            'success' => true,
            'message' => 'Live database schema fetched.'
        ]);
    }
}
