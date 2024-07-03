<?php

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/projects');


Route::resource('projects', ProjectController::class);
Route::get('/projects', [ProjectController::class, 'index'])->name('projects');
Route::get('/projects/{project:slug}', [ProjectController::class, 'chat'])->name('projects.show');
Route::get('/projects/delete/{project:slug}', [ProjectController::class, 'destroy'])->name('projects.delete');
