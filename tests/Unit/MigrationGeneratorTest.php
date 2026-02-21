<?php

namespace Ekosuprianto96\VisualMigrator\Tests\Unit;

use Orchestra\Testbench\TestCase;
use Ekosuprianto96\VisualMigrator\Services\MigrationGenerator;
use Ekosuprianto96\VisualMigrator\VisualMigratorServiceProvider;
use Illuminate\Support\Str;

class MigrationGeneratorTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [VisualMigratorServiceProvider::class];
    }
    private MigrationGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new MigrationGenerator();
    }

    /** @test */
    public function it_can_map_visual_types_to_blueprint_methods()
    {
        // Using Reflection to test protected method
        $reflection = new \ReflectionClass(MigrationGenerator::class);
        $method = $reflection->getMethod('generateColumnLine');
        $method->setAccessible(true);

        $testCases = [
            ['field' => ['name' => 'title', 'type' => 'String'], 'expected' => "\$table->string('title');"],
            ['field' => ['name' => 'age', 'type' => 'Number'], 'expected' => "\$table->integer('age');"],
            ['field' => ['name' => 'is_active', 'type' => 'Boolean'], 'expected' => "\$table->boolean('is_active');"],
            ['field' => ['name' => 'published_at', 'type' => 'Date'], 'expected' => "\$table->timestamp('published_at');"],
            ['field' => ['name' => 'meta', 'type' => 'Object'], 'expected' => "\$table->json('meta');"],
            ['field' => ['name' => 'tags', 'type' => 'Array'], 'expected' => "\$table->json('tags');"],
            ['field' => ['name' => 'total', 'type' => 'BIGINT', 'unsigned' => true], 'expected' => "\$table->bigInteger('total')->unsigned();"],
            ['field' => ['name' => 'count', 'type' => 'INT', 'index' => true], 'expected' => "\$table->integer('count')->index();"],
            ['field' => ['name' => 'status', 'type' => 'VARCHAR', 'defaultValue' => 'active', 'typeParams' => '255'], 'expected' => "\$table->string('status', 255)->default('active');"],
            ['field' => ['name' => 'price', 'type' => 'DECIMAL', 'defaultValue' => '0', 'typeParams' => '10,2'], 'expected' => "\$table->decimal('price', 10, 2)->default(0);"],
            ['field' => ['name' => 'user_uuid', 'type' => 'UUID'], 'expected' => "\$table->uuid('user_uuid');"],
        ];

        foreach ($testCases as $case) {
            $line = $method->invoke($this->generator, $case['field']);
            $this->assertEquals($case['expected'], $line);
        }
    }

    /** @test */
    public function it_handles_nullable_and_unique_modifiers()
    {
        $reflection = new \ReflectionClass(MigrationGenerator::class);
        $method = $reflection->getMethod('generateColumnLine');
        $method->setAccessible(true);

        $field = [
            'name' => 'email',
            'type' => 'String',
            'required' => false,
            'unique' => true
        ];

        $line = $method->invoke($this->generator, $field);
        $this->assertEquals("\$table->string('email')->nullable()->unique();", $line);
    }

    /** @test */
    public function it_handles_primary_keys()
    {
        $reflection = new \ReflectionClass(MigrationGenerator::class);
        $method = $reflection->getMethod('generateColumnLine');
        $method->setAccessible(true);

        // BigInt ID (default)
        $fieldBigId = ['name' => 'id', 'type' => 'BIGINT', 'key' => true];
        $this->assertEquals("\$table->id();", $method->invoke($this->generator, $fieldBigId));

        // Int ID
        $fieldIntId = ['name' => 'id', 'type' => 'INT', 'key' => true];
        $this->assertEquals("\$table->increments('id');", $method->invoke($this->generator, $fieldIntId));

        // UUID Primary
        $fieldUuid = ['name' => 'custom_uuid', 'type' => 'UUID', 'key' => true];
        $this->assertEquals("\$table->uuid('custom_uuid')->primary();", $method->invoke($this->generator, $fieldUuid));
    }
}
