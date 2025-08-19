<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StateApiController;

Route::prefix('states')->name('api.states.')->group(function () {
    Route::get('/', [StateApiController::class, 'index'])->name('index');

    Route::get('{cve_ent}', [StateApiController::class, 'show'])
        ->whereNumber('cve_ent')
        ->name('show');

    Route::post('sync', [StateApiController::class, 'sync'])->name('sync');

    Route::post('deduplicate', [StateApiController::class, 'deduplicate'])
        ->name('deduplicate');
});
