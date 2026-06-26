<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestModelController;

Route::get('/', [TestModelController::class, 'index']);
Route::post('/analyze', [TestModelController::class, 'analyze'])->name('analyze');
