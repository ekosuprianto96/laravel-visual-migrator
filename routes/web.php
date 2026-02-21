<?php

use Illuminate\Support\Facades\Route;
use Ekosuprianto96\VisualMigrator\Http\Controllers\MigratorController;

Route::get('/', [MigratorController::class, 'index'])->name('visual-migrator.index');
