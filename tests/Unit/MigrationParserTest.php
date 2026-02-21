<?php

namespace Ekosuprianto96\VisualMigrator\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Ekosuprianto96\VisualMigrator\Services\MigrationParser;
use Ekosuprianto96\VisualMigrator\VisualMigratorServiceProvider;
use Illuminate\Support\Str;

class MigrationParserTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [VisualMigratorServiceProvider::class];
    }
    private MigrationParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new MigrationParser();
    }

    /** @test */
    public function it_can_map_laravel_types_back_to_visual_types()
    {
        $reflection = new \ReflectionClass(MigrationParser::class);
        $method = $reflection->getMethod('mapLaravelTypeToVisual');
        $method->setAccessible(true);

        $testCases = [
            'string' => 'VARCHAR',
            'text' => 'TEXT',
            'integer' => 'INT',
            'bigInteger' => 'BIGINT',
            'boolean' => 'BOOLEAN',
            'timestamp' => 'DATE',
            'json' => 'JSON',
        ];

        foreach ($testCases as $laravelType => $expected) {
            $this->assertEquals($expected, $method->invoke($this->parser, $laravelType, 'mysql'));
        }

        // Test PostgreSQL specific
        $this->assertEquals('SERIAL', $method->invoke($this->parser, 'integer', 'pgsql'));
        $this->assertEquals('BIGSERIAL', $method->invoke($this->parser, 'id', 'pgsql'));
        $this->assertEquals('JSONB', $method->invoke($this->parser, 'json', 'pgsql'));
    }

    /** @test */
    public function it_can_extract_collection_data_from_migration_content()
    {
        $reflection = new \ReflectionClass(MigrationParser::class);
        $method = $reflection->getMethod('extractCollection');
        $method->setAccessible(true);

        $content = "
            Schema::create('posts', function (Blueprint \$table) {
                \$table->id();
                \$table->string('title');
                \$table->text('body')->nullable();
                \$table->boolean('is_published')->default(false);
                \$table->timestamps();
            });
        ";

        $result = $method->invoke($this->parser, 'posts', $content);
        $collection = $result['collection'];

        $this->assertEquals('Posts', $collection['data']['label']);
        $this->assertCount(6, $collection['data']['fields']); // id, title, body, is_published, created_at, updated_at

        $fieldNames = array_column($collection['data']['fields'], 'name');
        $this->assertContains('id', $fieldNames);
        $this->assertContains('title', $fieldNames);
        $this->assertContains('body', $fieldNames);
        $this->assertContains('created_at', $fieldNames);
        $this->assertContains('updated_at', $fieldNames);
    }

    /** @test */
    public function it_can_extract_foreign_keys_for_edges()
    {
        $reflection = new \ReflectionClass(MigrationParser::class);
        $method = $reflection->getMethod('extractCollection');
        $method->setAccessible(true);

        $content = "
            Schema::create('comments', function (Blueprint \$table) {
                \$table->id();
                \$table->foreignId('post_id')->constrained('posts');
                \$table->foreign('user_id')->references('id')->on('users');
            });
        ";

        $result = $method->invoke($this->parser, 'comments', $content);
        $edges = $result['edges'];

        $this->assertCount(2, $edges);
        
        // Edge 1: post_id
        $this->assertEquals('comments', $edges[0]['source_table']);
        $this->assertEquals('post_id', $edges[0]['source_field']);
        $this->assertEquals('posts', $edges[0]['target_table']);

        // Edge 2: user_id
        $this->assertEquals('comments', $edges[1]['source_table']);
        $this->assertEquals('user_id', $edges[1]['source_field']);
        $this->assertEquals('users', $edges[1]['target_table']);
    }
}
