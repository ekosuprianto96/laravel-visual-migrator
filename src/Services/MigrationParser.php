<?php

namespace Ekosuprianto96\VisualMigrator\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MigrationParser
{
    /**
     * Parse existing migrations into a visual schema JSON.
     *
     * @return array
     */
    public function parse()
    {
        $migrationFiles = File::files(database_path('migrations'));
        $tableContents = [];
        $ignoredTables = config('visual-migrator.ignore_migrations', []);

        foreach ($migrationFiles as $file) {
            $content = File::get($file->getPathname());
            
            // Match Schema::create or Schema::table
            if (preg_match_all("/Schema::(create|table)\(['\"](.+?)['\"]/i", $content, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $tableName = $match[2];

                    // Skip ignored tables
                    if (in_array($tableName, $ignoredTables)) {
                        continue;
                    }

                    if (!isset($tableContents[$tableName])) {
                        $tableContents[$tableName] = "";
                    }
                    $tableContents[$tableName] .= "\n" . $content;
                }
            }
        }

        $collections = [];
        $rawEdges = [];
        $fieldToIdMap = []; // [tableName => [fieldName => fieldId]]
        
        $connection = config('database.default');
        $driver = config('database.connections.' . $connection . '.driver', 'mysql');

        foreach ($tableContents as $tableName => $mergedContent) {
            $result = $this->extractCollection($tableName, $mergedContent, $driver);
            $collections[] = $result['collection'];
            $rawEdges = array_merge($rawEdges, $result['edges']);
            
            $fieldsMap = [];
            foreach ($result['collection']['data']['fields'] as $field) {
                $fieldsMap[$field['name']] = $field['id'];
            }
            $fieldToIdMap[$tableName] = $fieldsMap;
        }

        // 3. Apply Metadata (Layout positions & Permanent IDs)
        $metaData = $this->getLayoutMeta();
        foreach ($collections as &$col) {
            $tableName = $col['data']['tableName'];
            if (isset($metaData[$tableName])) {
                $col['position'] = $metaData[$tableName]['position'] ?? $col['position'];
                // Optional: keep consistency of IDs if provided in meta
                if (isset($metaData[$tableName]['id'])) {
                    $col['id'] = $metaData[$tableName]['id'];
                }
            }
        }

        // 4. Resolve Table Name to Collection ID for edges (Now using final IDs from metadata)
        $edges = [];
        $tableToIdMap = [];
        foreach ($collections as $col) {
            $tableToIdMap[$col['data']['tableName']] = $col['id'];
        }

        foreach ($rawEdges as $rawEdge) {
            $sourceId = $tableToIdMap[$rawEdge['source_table']] ?? null;
            $targetId = $tableToIdMap[$rawEdge['target_table']] ?? null;

            if ($sourceId && $targetId) {
                // Map field names to their visual IDs (e.g. 'id_group' -> 'f-123')
                $sourceHandle = $fieldToIdMap[$rawEdge['source_table']][$rawEdge['source_field']] ?? $rawEdge['source_field'];
                $targetHandle = $fieldToIdMap[$rawEdge['target_table']][$rawEdge['target_field']] ?? $rawEdge['target_field'];

                $edges[] = [
                    'id' => 'e-' . Str::random(8),
                    'source' => $sourceId,
                    'target' => $targetId,
                    'sourceHandle' => $sourceHandle,
                    'targetHandle' => $targetHandle,
                    'type' => 'smoothstep',
                    'data' => [
                        'relationType' => 'oneToMany'
                    ]
                ];
            }
        }

        $connection = config('database.default');
        $driver = config('database.connections.' . $connection . '.driver', 'mysql');
        $dbName = config('database.connections.' . $connection . '.database', $connection);
        
        $dbType = match ($driver) {
            'pgsql' => 'PostgreSQL',
            'mongodb' => 'MongoDB',
            default => 'MySQL',
        };

        return [
            'databases' => [
                ['id' => 'db-main', 'name' => $dbName, 'type' => $dbType]
            ],
            'activeDatabaseId' => 'db-main',
            'collections' => $collections,
            'edges' => $edges
        ];
    }

    /**
     * Extract table data from migration content.
     */
    protected function extractCollection($tableName, $content, $driver = 'mysql')
    {
        $fields = [];
        $rawEdges = [];
        $collectionId = 'col-' . Str::random(8);

        // 1. Handle $table->id() or $table->bigIncremental('id') or $table->increments('id')
        if (preg_match("/\\\$table->(id|increments|bigIncrements|uuid)\((['\"](.+?)['\"])?\)/i", $content, $idMatches)) {
            $idName = $idMatches[3] ?? 'id';
            $type = $this->mapLaravelTypeToVisual($idMatches[1], $driver);
            
            $fields[] = [
                'id' => 'f-' . Str::random(8),
                'name' => $idName,
                'type' => $type,
                'required' => true,
                'unique' => true,
                'key' => true,
                'primaryKey' => true,
                'autoIncrement' => in_array($idMatches[1], ['id', 'increments', 'bigIncrements'])
            ];
        }

        // 2. Match other column definitions (capturing name and optional params)
        preg_match_all("/\\\$table->(?!id|increments|bigIncrements|uuid|timestamps|softDeletes|rememberToken|foreign)(\w+)\s*\(\s*['\"](.+?)['\"]\s*(?:,\s*(.+?)\s*)?\)/i", $content, $matches, PREG_SET_ORDER);
        
        $fieldNames = array_map(fn($f) => $f['name'], $fields);

        foreach ($matches as $match) {
            $line = $match[0];
            $laravelMethod = $match[1];
            $columnName = $match[2];
            $typeParams = isset($match[3]) ? trim($match[3]) : '';
            
            // Avoid duplicates (e.g. if a column is defined twice or matched twice)
            if (in_array($columnName, $fieldNames)) continue;

            $type = $this->mapLaravelTypeToVisual($laravelMethod, $driver);
            
            $field = [
                'id' => 'f-' . Str::random(8),
                'name' => $columnName,
                'type' => $type,
                'typeParams' => $typeParams, // Store additional params like length or scale
                'required' => !str_contains($line, '->nullable()'),
                'unique' => str_contains($line, '->unique()'),
                'unsigned' => str_contains($line, '->unsigned()'),
                'index' => str_contains($line, '->index()'),
                'key' => str_contains($line, '->primary()'),
                'primaryKey' => str_contains($line, '->primary()')
            ];

            // Extract Default Value
            if (preg_match("/->default\((.+?)\)/i", $line, $defaultMatches)) {
                $val = trim($defaultMatches[1], " '\"");
                $field['defaultValue'] = $val;
            } elseif (str_contains($line, '->useCurrent()')) {
                $field['defaultValue'] = 'CURRENT_TIMESTAMP';
            }

            $fields[] = $field;
            $fieldNames[] = $columnName;
        }

        // 3. Extract Foreign Keys for Edges
        // 3a. From $table->foreignId('user_id')->constrained('users')
        if (preg_match_all("/\\\$table->foreignId\s*\(\s*['\"](.+?)['\"]\s*\)\s*->constrained\s*\(\s*['\"](.+?)['\"]\s*\)/i", $content, $fkIdMatches, PREG_SET_ORDER)) {
            foreach ($fkIdMatches as $fk) {
                $rawEdges[] = [
                    'source_table' => $tableName,
                    'source_field' => $fk[1],
                    'target_table' => $fk[2],
                    'target_field' => 'id', // Default Laravel assumption
                ];
            }
        }

        // 3b. From $table->foreign('user_id')->references('id')->on('users')
        if (preg_match_all("/\\\$table->foreign\s*\(\s*['\"](.+?)['\"]\s*\)\s*->references\s*\(\s*['\"](.+?)['\"]\s*\)\s*->on\s*\(\s*['\"](.+?)['\"]\s*\)(?:->onDelete\(['\"](.+?)['\"]\))?/i", $content, $fkMatches, PREG_SET_ORDER)) {
            foreach ($fkMatches as $fk) {
                $rawEdges[] = [
                    'source_table' => $tableName,
                    'source_field' => $fk[1],
                    'target_table' => $fk[3],
                    'target_field' => $fk[2],
                    'onDelete' => $fk[4] ?? 'cascade'
                ];
            }
        }

        // 4. Special cases: Timestamps & Soft Deletes
        if (str_contains($content, '$table->timestamps()')) {
            if (!in_array('created_at', $fieldNames)) $fields[] = ['id' => 'f-ts-c', 'name' => 'created_at', 'type' => 'Date', 'required' => false];
            if (!in_array('updated_at', $fieldNames)) $fields[] = ['id' => 'f-ts-u', 'name' => 'updated_at', 'type' => 'Date', 'required' => false];
        }

        if (str_contains($content, '$table->softDeletes()') && !in_array('deleted_at', $fieldNames)) {
            $fields[] = ['id' => 'f-sd', 'name' => 'deleted_at', 'type' => 'Date', 'required' => false];
        }

        // Generate a deterministic ID based on table name as fallback
        // This ensures the ID stays the same even if not yet saved to layoutMeta
        $fallbackId = 'col-' . substr(md5($tableName), 0, 8);

        return [
            'collection' => [
                'id' => $fallbackId, // Will be overwritten by getLayoutMeta() in parse() if exists
                'databaseId' => 'db-main',
                'type' => 'collection',
                'position' => ['x' => rand(50, 800), 'y' => rand(50, 500)], // Default random, overwritten by meta if available
                'data' => [
                    'label' => $tableName,
                    'tableName' => $tableName,
                    'fields' => $fields
                ]
            ],
            'edges' => $rawEdges
        ];
    }

    /**
     * Map Laravel Blueprint types back to visual types.
     */
    protected function mapLaravelTypeToVisual($laravelType, $driver = 'mysql')
    {
        $type = strtolower($laravelType);
        $isPostgres = ($driver === 'pgsql');

        switch ($type) {
            case 'id':
            case 'foreignid':
            case 'bigincrements':
            case 'biginteger':
            case 'unsignedbiginteger':
                return $isPostgres ? 'BIGSERIAL' : 'BIGINT';
            case 'increments':
            case 'integer':
            case 'unsignedinteger':
            case 'mediuminteger':
                return $isPostgres ? 'SERIAL' : 'INT';
            case 'tinyinteger': return 'TINYINT';
            case 'smallinteger': return 'SMALLINT';
            case 'decimal': return 'DECIMAL';
            case 'float': return 'FLOAT';
            case 'double': return 'DOUBLE';
            case 'string':
            case 'char': return 'VARCHAR';
            case 'text': return 'TEXT';
            case 'mediumtext': return 'MEDIUMTEXT';
            case 'longtext': return 'LONGTEXT';
            case 'uuid': return 'UUID';
            case 'boolean': return 'BOOLEAN';
            case 'timestamp':
            case 'timestamps':
            case 'datetime':
            case 'date': return 'DATE';
            case 'json':
            case 'jsonb': return 'JSON';
            default: return strtoupper($laravelType);
        }
    }
    /**
     * Get layout metadata from Database or fallback file.
     */
    protected function getLayoutMeta(): array
    {
        $metaData = [];

        // 1. Try reading from Database first
        try {
            $entries = DB::table('visual_migrator_entries')
                ->where('type', 'layout')
                ->get();
                
            foreach ($entries as $entry) {
                $content = json_decode($entry->content, true);
                if ($content) {
                    $metaData[$entry->key] = $content;
                }
            }
        } catch (\Exception $e) {
            // Database might not be migrated yet
        }

        // 2. Fallback/Merge with JSON file
        $metaPath = database_path('migrations/.visual-migrator-meta.json');
        if (File::exists($metaPath)) {
            $fileMeta = json_decode(File::get($metaPath), true) ?: [];
            // DB has priority over JSON
            $metaData = array_merge($fileMeta, $metaData);
        }

        return $metaData;
    }
}
