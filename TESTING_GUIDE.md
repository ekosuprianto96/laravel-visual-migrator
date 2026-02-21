# Testing Guide: Laravel Visual Migrator

This document explains how to test your Laravel package to ensure Mongo Diagram integration works perfectly.

## 1. Manual Testing via Laravel Project

The fastest way is to integrate the package into an existing Laravel project on your local machine.

### Step A: Link Local Package
Open the `composer.json` in your Laravel (host) project and add:

```json
"repositories": [
    {
        "type": "path",
        "url": "../path/to/mongo-diagram/packages/laravel"
    }
],
"require": {
    "ekosuprianto96/laravel-visual-migrator": "dev-main"
}
```

### Step B: Install & Publish
Run the following command in your Laravel project terminal:

```bash
composer update ekosuprianto96/laravel-visual-migrator
php artisan vendor:publish --tag=visual-migrator-assets --force
```

### Step C: Browser Verification
1. Run `php artisan serve`.
2. Access `http://localhost:8000/visual-migrator`.
3. Ensure the Mongo Diagram UI appears and can communicate with the backend API.

---

## 2. Automated Testing (Orchestra Testbench)

For professional testing, we use **Orchestra Testbench** to run unit tests without requiring a full Laravel project.

### Step A: Install Dependencies
Inside the `packages/laravel` folder, run:
```bash
composer require --dev orchestra/testbench
```

### Step B: Create Test File
Create `packages/laravel/tests/Feature/MigrationGeneratorTest.php`:

```php
<?php

namespace Ekosuprianto96\VisualMigrator\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Ekosuprianto96\VisualMigrator\Services\MigrationGenerator;
use Ekosuprianto96\VisualMigrator\VisualMigratorServiceProvider;

class MigrationGeneratorTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [VisualMigratorServiceProvider::class];
    }

    /** @test */
    public function it_can_generate_migration_content()
    {
        $generator = new MigrationGenerator();
        $schema = [
            'collections' => [
                [
                    'id' => '1',
                    'data' => [
                        'label' => 'TesTable',
                        'fields' => [
                            ['name' => 'title', 'type' => 'String', 'required' => true]
                        ]
                    ]
                ]
            ]
        ];

        $files = $generator->generate($schema);
        $this->assertCount(1, $files);
        $this->assertEquals('tes_table', $files[0]['table']);
    }
}
```

---

## 3. Testing Checklist
- [ ] Is the `/visual-migrator` dashboard accessible?
- [ ] Does `GET /api/schema` return JSON from the migrations folder?
- [ ] Does `POST /api/sync` successfully create new files in `database/migrations`?
- [ ] Are data types (String, Number, Date) mapped correctly in PHP files?
