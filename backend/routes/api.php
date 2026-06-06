<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

// ─── PUBLIC (mahasiswa anonim) ────────────────────────────────
Route::post('/tickets', [TicketController::class, 'store']);
Route::get('/tickets/{id}/status', [TicketController::class, 'status']);

// ─── AUTH ─────────────────────────────────────────────────────
Route::post('/auth/login', [AuthController::class, 'login']);

// ─── ADMIN ──────────────────────────────────────
Route::get('/tickets', [TicketController::class, 'index']);
Route::patch('/tickets/{id}', [TicketController::class, 'update']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
});