<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/jobs', [\App\Http\Controllers\MoviesJobsController::class, 'index'])->name("jobs.index");
Route::get('/jobs/pause', [\App\Http\Controllers\MoviesJobsController::class, 'pause'])->name("jobs.pause");
Route::get('/jobs/resume', [\App\Http\Controllers\MoviesJobsController::class, 'resume'])->name("jobs.resume");

Route::get('/setting', [\App\Http\Controllers\SettingsController::class, 'index'])->name("setting.index");
Route::put('/setting/{key}', [\App\Http\Controllers\SettingsController::class, 'update'])->name("setting.update");

Route::post('/path', [\App\Http\Controllers\MoviesPathsController::class, 'create'])->name("path.create");
Route::get('/path/delete/{id}', [\App\Http\Controllers\MoviesPathsController::class, 'delete'])->name("path.delete");
Route::get('/path/activate/{id}', [\App\Http\Controllers\MoviesPathsController::class, 'activate'])->name("path.activate");
Route::get('/path/deactivate/{id}', [\App\Http\Controllers\MoviesPathsController::class, 'deactivate'])->name("path.deactivate");

Route::get('/', [\App\Http\Controllers\MoviesController::class, 'index'])->name("movies");
Route::get('/{id}', [\App\Http\Controllers\MoviesController::class, 'view'])->name("movies.view");
Route::put('/{id}/rename', [\App\Http\Controllers\MoviesController::class, 'rename'])->name("movies.rename");
Route::put('/{id}/move', [\App\Http\Controllers\MoviesController::class, 'move'])->name("movies.move");
