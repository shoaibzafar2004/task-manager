<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\MarkdownPreviewController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskChecklistController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TaskController::class, 'index'])->name('tasks.index');
Route::get('/history', HistoryController::class)->name('history');
Route::get('/export.json', [ExportController::class, 'json'])->name('export.json');
Route::get('/export.csv', [ExportController::class, 'csv'])->name('export.csv');

Route::prefix('tasks')->name('tasks.')->controller(TaskController::class)->group(function () {
    Route::post('/', 'store')->name('store');
    Route::post('/reorder', 'reorder')->name('reorder');
    Route::get('/{task}', 'show')->name('show')->withTrashed();
    Route::get('/{task}/edit', 'edit')->name('edit');
    Route::put('/{task}', 'update')->name('update');
    Route::delete('/{task}', 'destroy')->name('destroy');
    Route::patch('/{task}/toggle', 'toggle')->name('toggle');
    Route::patch('/{task}/checklist', TaskChecklistController::class)->name('checklist');
    Route::patch('/{id}/restore', 'restore')->name('restore');
});

Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

Route::post('/labels', [LabelController::class, 'store'])->name('labels.store');
Route::delete('/labels/{label}', [LabelController::class, 'destroy'])->name('labels.destroy');

Route::post('/markdown/preview', MarkdownPreviewController::class)->name('markdown.preview');
