<?php

namespace Ekosuprianto96\VisualMigrator\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class MigrationGenerator
{
    /**
     * Generate Laravel migration files from schema JSON.
     *
     * @param array $schema
     * @return array
     */
    public function generate(array $schema)
    {
        $collections = $schema['collections'] ?? [];
        $edges = $schema['edges'] ?? [];
        $results = [];

        // Build Table Map for resolving IDs to names
        $tableMap = [];
        foreach ($collections as $col) {
            $tableMap[$col['id']] = Str::snake($col['data']['label']);
        }

        $ignoredTables = config('visual-migrator.ignore_migrations', []);

        foreach ($collections as $collection) {
            $tableName = Str::snake($collection['data']['label']);

            // Skip ignored tables
            if (in_array($tableName, $ignoredTables)) {
                continue;
            }

            $fields = $collection['data']['fields'] ?? [];
            
            $content = $this->buildMigrationContent($tableName, $fields, $edges, $collection['id'], $tableMap);
            $filename = $this->saveMigrationFile($tableName, $content);
            
            $results[] = [
                'table' => $tableName,
                'file' => $filename
            ];
        }

        $this->saveLayoutMeta($collections);

        return $results;
    }

    /**
     * Save node positions to a hidden metadata file.
     */
    public function saveLayoutMeta(array $collections): void
    {
        try {
            foreach ($collections as $col) {
                $tableName = Str::snake($col['data']['label'] ?? '');
                if (!$tableName) continue;
                
                $data = [
                    'id' => $col['id'],
                    'position' => $col['position']
                ];

                DB::table('visual_migrator_entries')->updateOrInsert(
                    ['key' => $tableName, 'type' => 'layout'],
                    [
                        'uuid' => (string) Str::uuid(),
                        'content' => json_encode($data),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        } catch (\Exception $e) {
            // Log fallback to file if database table not yet migrated
            $this->saveLayoutToFallbackFile($collections);
        }
    }

    /**
     * Fallback to file storage if database is not available.
     */
    protected function saveLayoutToFallbackFile(array $collections): void
    {
        $metaPath = database_path('migrations/.visual-migrator-meta.json');
        $metaData = File::exists($metaPath) ? json_decode(File::get($metaPath), true) : [];
        
        foreach ($collections as $col) {
            $tableName = Str::snake($col['data']['label'] ?? '');
            if (!$tableName) continue;
            
            $metaData[$tableName] = [
                'id' => $col['id'],
                'position' => $col['position']
            ];
        }
        
        File::put($metaPath, json_encode($metaData, JSON_PRETTY_PRINT));
        $this->mirrorToStandalone($metaPath, 'database/migrations/.visual-migrator-meta.json');
    }

    /**
     * Build the PHP content for the migration.
     */
    protected function buildMigrationContent($tableName, $fields, $edges, $collectionId, $tableMap)
    {
        $rows = [];
        $hasTimestamps = false;
        
        foreach ($fields as $field) {
            if (in_array($field['name'], ['created_at', 'updated_at'])) {
                $hasTimestamps = true;
                continue;
            }
            $rows[] = $this->generateColumnLine($field);
        }

        if ($hasTimestamps) {
            $rows[] = "\$table->timestamps();";
        }

        // Handle Foreign Keys from fields directly if they have foreignKey=true
        foreach ($fields as $field) {
            if ($field['foreignKey'] ?? false) {
                // Check if this field appears in Edges as well, to avoid duplicate FK definitions
                // Actually, relying on Edges is better for visual consistency, 
                // but sometimes fields are marked as FK without explicit edge in payload.
                $rows[] = $this->generateForeignKeyFromField($field);
            }
        }

        // Handle Foreign Keys from Edges (if not already handled by field FK)
        $processedFieldNames = array_map(fn($f) => $f['name'], array_filter($fields, fn($f) => $f['foreignKey'] ?? false));
        
        foreach ($edges as $edge) {
            if ($edge['target'] === $collectionId) {
                $targetHandle = $edge['targetHandle'];
                
                // Find column name from targetHandle ID
                $localFieldName = 'idk';
                foreach ($fields as $f) {
                    if ($f['id'] === $targetHandle) {
                        $localFieldName = $f['name'];
                        break;
                    }
                }

                if (in_array($localFieldName, $processedFieldNames)) continue;

                $sourceTableName = $tableMap[$edge['source']] ?? 'idk';
                $rows[] = $this->generateForeignKeyLine($edge, $sourceTableName, $localFieldName);
            }
        }

        $blueprint = implode("\n            ", array_filter($rows));

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            {$blueprint}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};
PHP;
    }

    /**
     * Map visual types to Laravel Blueprint methods.
     */
    protected function generateColumnLine($field)
    {
        $name = $field['name'];
        $type = $field['type'];

        // Special case: Timestamps
        if (in_array($name, ['created_at', 'updated_at'])) {
            return "// Handled by \$table->timestamps()";
        }
        
        // Special case: Soft Deletes
        if ($name === 'deleted_at') {
            return "\$table->softDeletes();";
        }
        
        $method = 'string';
        $params = "'{$name}'";

        // Mapping visual types back to Laravel Blueprint methods
        switch (strtoupper($type)) {
            case 'BIGINT':
            case 'BIGSERIAL': $method = 'bigInteger'; break;
            case 'INT':
            case 'SERIAL':
            case 'NUMBER': $method = 'integer'; break;
            case 'TINYINT': $method = 'tinyInteger'; break;
            case 'SMALLINT': $method = 'smallInteger'; break;
            case 'DECIMAL':
                $method = 'decimal';
                if ($typeParams = ($field['typeParams'] ?? '')) {
                    $parts = explode(',', $typeParams);
                    $precision = trim($parts[0] ?? '10');
                    $scale = trim($parts[1] ?? '2');
                    $params .= ", {$precision}, {$scale}";
                } else {
                    $params .= ", 10, 2";
                }
                break;
            case 'FLOAT': $method = 'float'; break;
            case 'DOUBLE': $method = 'double'; break;
            case 'BOOLEAN': $method = 'boolean'; break;
            case 'DATE':
            case 'DATETIME':
            case 'TIMESTAMP': $method = 'timestamp'; break;
            case 'JSON':
            case 'JSONB':
            case 'ARRAY':
            case 'OBJECT': $method = 'json'; break;
            case 'UUID': $method = 'uuid'; break;
            case 'TEXT': $method = 'text'; break;
            case 'MEDIUMTEXT': $method = 'mediumText'; break;
            case 'LONGTEXT': $method = 'longText'; break;
            case 'CHAR': $method = 'char'; break;
            case 'VARCHAR':
            case 'STRING':
            case 'OBJECTID':
                $method = 'string';
                if ($typeParams = ($field['typeParams'] ?? '')) {
                    $len = trim($typeParams);
                    if (is_numeric($len)) {
                        $params .= ", {$len}";
                    }
                }
                break;
            default: $method = strtolower($type); break;
        }

        if (($field['key'] ?? false) || ($field['primaryKey'] ?? false)) {
            if ($name === 'id') {
                if (in_array(strtoupper($type), ['INT', 'SERIAL'])) {
                    return "\$table->increments('id');";
                }
                return "\$table->id();"; // Default to bigIncrements
            }
            return "\$table->{$method}('{$name}')->primary();";
        }

        $line = "\$table->{$method}({$params})";

        if ($field['unsigned'] ?? false) {
            $line .= "->unsigned()";
        }
        if (!($field['required'] ?? true)) {
            $line .= "->nullable()";
        }
        if ($field['unique'] ?? false) {
            $line .= "->unique()";
        }
        if ($field['index'] ?? false) {
            $line .= "->index()";
        }
        
        if (isset($field['defaultValue']) && $field['defaultValue'] !== '') {
            $default = $field['defaultValue'];
            if (strtoupper($default) === 'CURRENT_TIMESTAMP' || strtolower($default) === 'now()') {
                $line .= "->useCurrent()";
            } elseif (is_numeric($default)) {
                $line .= "->default({$default})";
            } elseif (in_array(strtolower($default), ['true', 'false'])) {
                $line .= "->default(" . strtolower($default) . ")";
            } else {
                $line .= "->default('{$default}')";
            }
        }

        return $line . ";";
    }

    /**
     * Handle relationship in migration from field metadata.
     */
    protected function generateForeignKeyFromField($field)
    {
        $name = $field['name'];
        $refTable = Str::snake($field['referencesTable'] ?? '');
        $refCol = $field['referencesColumn'] ?? 'id';
        
        if (!$refTable) return null;

        $line = "\$table->foreign('{$name}')->references('{$refCol}')->on('{$refTable}')";
        
        if ($onDelete = ($field['onDelete'] ?? '')) {
            $line .= "->onDelete('" . strtolower($onDelete) . "')";
        }
        if ($onUpdate = ($field['onUpdate'] ?? '')) {
            $line .= "->onUpdate('" . strtolower($onUpdate) . "')";
        }
        
        return $line . ";";
    }

    /**
     * Handle relationship in migration from visual edges.
     */
    protected function generateForeignKeyLine($edge, $sourceTableName, $localFieldName = null)
    {
        $name = $localFieldName ?? $edge['targetHandle'];
        
        $line = "\$table->foreign('{$name}')->references('id')->on('{$sourceTableName}')";
        
        if ($onDelete = ($edge['onDelete'] ?? 'cascade')) {
            $line .= "->onDelete('" . strtolower($onDelete) . "')";
        }
        
        return $line . ";";
    }

    /**
     * Save the migration file to disk.
     */
    protected function saveMigrationFile($tableName, $content)
    {
        $migrationsPath = database_path('migrations');
        $existingFiles = File::files($migrationsPath);
        
        $filename = null;
        
        // Look for existing "create_{$tableName}_table" file
        foreach ($existingFiles as $file) {
            if (str_contains($file->getFilename(), "_create_{$tableName}_table.php")) {
                $filename = $file->getFilename();
                break;
            }
        }

        if (!$filename) {
            $timestamp = date('Y_m_d_His');
            $filename = "{$timestamp}_create_{$tableName}_table.php";
        }

        $path = $migrationsPath . '/' . $filename;
        
        // Actual write to file system
        try {
            File::put($path, $content);
            
            // Mirror to standalone path
            $this->mirrorToStandalone($path, 'database/migrations/' . $filename);
        } catch (\Exception $e) {
            // Silently fail or log if outside Laravel context
        }
        
        return $filename;
    }

    /**
     * Mirror a file to the standalone project path if configured.
     */
    protected function mirrorToStandalone($sourcePath, $relativeDestination)
    {
        $standalonePath = env('VISUAL_MIGRATOR_STANDALONE_PATH');
        
        if ($standalonePath && File::exists($standalonePath)) {
            $destination = rtrim($standalonePath, '/') . '/' . ltrim($relativeDestination, '/');
            
            // Ensure directory exists
            $dir = dirname($destination);
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true);
            }
            
            File::copy($sourcePath, $destination);
        }
    }

    /**
     * Sync the entire package source code to the standalone project.
     */
    public function syncPackageSource($standalonePath)
    {
        $packageRoot = realpath(__DIR__ . '/../../');
        $folders = ['src', 'config', 'resources', 'routes', 'tests', 'dist'];
        
        foreach ($folders as $folder) {
            $source = $packageRoot . '/' . $folder;
            $destination = rtrim($standalonePath, '/') . '/' . $folder;
            
            if (File::isDirectory($source)) {
                File::copyDirectory($source, $destination);
            }
        }

        // Also sync files in package root
        $files = ['composer.json', 'README.md', 'CHANGELOG.md', 'LICENSE', 'CONTRIBUTING.md', 'TESTING_GUIDE.md', 'RELEASE_NOTES.md'];
        foreach ($files as $file) {
            $source = $packageRoot . '/' . $file;
            $destination = rtrim($standalonePath, '/') . '/' . $file;
            
            if (File::exists($source)) {
                File::copy($source, $destination);
            }
        }
    }
}
