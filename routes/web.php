<?php

use App\Http\Controllers\HistoryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TaskController::class, 'index'])->name('tasks.index');
Route::get('/history', HistoryController::class)->name('history');

Route::prefix('tasks')->name('tasks.')->controller(TaskController::class)->group(function () {
    Route::post('/', 'store')->name('store');
    Route::post('/reorder', 'reorder')->name('reorder');
    Route::get('/{task}/edit', 'edit')->name('edit');
    Route::put('/{task}', 'update')->name('update');
    Route::delete('/{task}', 'destroy')->name('destroy');
    Route::patch('/{task}/toggle', 'toggle')->name('toggle');
    Route::patch('/{id}/restore', 'restore')->name('restore');
});

Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
