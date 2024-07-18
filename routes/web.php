<?php

use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/projects');

Route::resource('projects', ProjectController::class);
Route::get('/projects', [ProjectController::class, 'index'])->name('projects');
Route::get('/projects/{project:slug}', [ProjectController::class, 'chat'])->name('projects.show');
Route::get('/projects/delete/{project:slug}', [ProjectController::class, 'destroy'])->name('projects.delete');
Route::get('/projects/{project:slug}/restore/{snapshotId}', [ProjectController::class, 'restoreSnapshot'])->name('projects.restore');

// Settings routes
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
