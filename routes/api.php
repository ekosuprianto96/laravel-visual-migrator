<?php

use Illuminate\Support\Facades\Route;
use Ekosuprianto96\VisualMigrator\Http\Controllers\MigratorController;

Route::prefix('api')->group(function () {
    Route::get('/schema', [MigratorController::class, 'getSchema']);
    Route::post('/sync', [MigratorController::class, 'syncSchema']);
    Route::post('/save-layout', [MigratorController::class, 'saveLayout']);
    Route::get('/live-db/{databaseId}', [MigratorController::class, 'getLiveDbSchema']);
});
