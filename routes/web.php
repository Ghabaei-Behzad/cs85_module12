<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AiContentController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ai-form', [AiContentController::class, 'showForm'])->name('ai.form');
Route::post('/ai-generate', [AiContentController::class, 'generate'])->name('ai.generate');