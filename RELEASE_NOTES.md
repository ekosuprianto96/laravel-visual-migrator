# Release Notes v2.6.0

v2.6.0 updates package dependencies and widens Laravel compatibility to support `illuminate/support` ^10.0, ^11.0, and ^12.0. The version bump also prepares for broader framework support.

# Release Notes v2.5.0

v2.5.0 brings **Laravel 12 Support** and enhanced **Database Compatibility**. 

We have listened to feedback regarding data loss of drafts upon page refresh and the annoyance of node positions being reset during synchronization. This release addresses all those issues with the "Smart Merge Architecture".

## What's New? 🚀

### 1. Smart Draft Persistence
You no longer need to worry if the browser accidentally refreshes. The new tables you are designing (Drafts) will remain in the diagram thanks to the intelligent merging between LocalStorage and Remote Schema.

### 2. Layout Stability (Anti-Reset)
One of the most significant improvements is visual coordinate locking. When you synchronize with the Laravel backend, the application will prioritize the node positions currently on your screen, preventing the diagram from suddenly becoming messy.

### 3. Dashboard Polish
Toolbar transitions have been refined to eliminate visual glitches. The toolbar is now more stable, responsive, and provides a more premium navigation experience.

## How to Update 🛠️

Simply run the following command in your Laravel project:

```bash
composer update ekosuprianto96/laravel-visual-migrator
```

If there are crucial asset changes, you might need to republish the assets (optional):

```bash
php artisan vendor:publish --tag=visual-migrator-assets --force
```

## Thank You 💖

Thank you for using **Laravel Visual Migrator**. Let's build better and faster database schemas!
