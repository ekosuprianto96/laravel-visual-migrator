# Laravel Visual Migrator

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ekosuprianto96/laravel-visual-migrator.svg?style=flat-square)](https://packagist.org/packages/ekosuprianto96/laravel-visual-migrator)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)

**Laravel Visual Migrator** is a powerful visual database schema design tool integrated directly into your Laravel application. Model your tables, fields, and relationships visually and transform them into Laravel migration files instantly.

---

## Key Features

- 🎨 **Visual Architecture Builder**: Design your database schema using a highly interactive and intuitive diagram interface powered by Vue Flow.
- 🔄 **Bidirectional Synchronization**:
    - **Import**: Automatically scan your `database/migrations` folder and visualize your existing tables.
    - **Export**: Generate clean, PSR-compliant Laravel migration PHP files from your visual design.
- 🛡️ **Draft & Persistence System**: Added tables (Drafts) are preserved in your browser's local storage until you officially save them to migrations, preventing work loss on refresh.
- � **Layout Stability**: Smart merging logic ensures that your custom node positions are preserved even after backend synchronization.
- ⚡ **Real-time Change Detection**: Automatically detects when migration files are modified on the server and notifies you to sync.
- � **Table Selection**: Choose specifically which tables you want to export or sync, giving you granular control over your codebase.

## Efficiency & Performance

- **Optimized Network Traffic**: Uses debounced requests for layout saving and throttled polling for change detection.
- **Tab Visibility Awareness**: Polling automatically pauses when the browser tab is inactive to save system resources.
- **Lightweight Metadata**: Relationship and layout data are stored efficiently in a single JSON file within your migrations folder.

---

## Installation

Install the package via composer:

```bash
composer require ekosuprianto96/laravel-visual-migrator
```

Publish and setup the package automatically:

```bash
php artisan visual-migrator:install
```

This command will publish assets, configuration, and offers to run the database migrations for you.

### Useful Commands

#### Reset Metadata
If you need to reset all visual layout data (node positions), you can use the refresh command:

```bash
php artisan visual-migrator:refresh
```

#### Cleanup Orphaned Data
If you delete migration files manually, you can clean up the orphaned visual metadata from the database:

```bash
php artisan visual-migrator:cleanup
```

## Testing

Run the unit tests using PHPUnit:

```bash
cd packages/laravel
composer install
./vendor/bin/phpunit
```

## Configuration

The configuration file is located at `config/visual-migrator.php`. You can customize the following options:

```php
return [
    /**
     * The URL path where the visual migrator dashboard will be accessible.
     * Default: 'visual-migrator'
     */
    'path' => 'visual-migrator',

    /**
     * The middleware applied to the visual migrator routes.
     * Usually 'web' is sufficient for local development.
     */
    'middleware' => ['web'],

    /**
     * List of migration tables to be ignored by the visual migrator.
     * These tables will not be parsed or displayed in the diagram dashboard.
     * Perfect for hiding Laravel system/boilerplate tables.
     */
    'ignore_migrations' => [
        'users', 'password_reset_tokens', 'failed_jobs', 
        'personal_access_tokens', 'cache', 'jobs', 
        'job_batches', 'sessions',
    ],
];
```

> [!IMPORTANT]
> For security reasons, the dashboard is **only accessible in the `local` environment** by default.

---

## Why Use Laravel Visual Migrator?

1. **Speed up Prototyping**: Stop writing migrations by hand. Drag, drop, and click to build your core architecture in minutes.
2. **Visual Documentation**: Your diagram *is* your living documentation. New team members can understand the database structure at a glance.
3. **Reduced Human Error**: Ensure foreign keys and data types are consistent across tables without worrying about syntax errors.
4. **Non-Destructive**: It works alongside your existing migrations. You can selectively sync only what you need.

## Future Roadmap

- [ ] **Visual Seeders**: Generate factory and seeder data directly from the diagram.
- [ ] **Export to Laravel Blueprint**: Support for the popular Laravel Blueprint syntax.
- [ ] **Collaborative Mode**: Real-time collaborative design for teams.
- [ ] **Reverse Engineering Support**: Advanced parsing for complex raw SQL dumps.

---

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Eko Suprianto](https://github.com/ekosuprianto96)
- [All Contributors](https://github.com/ekosuprianto96/laravel-visual-migrator/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
