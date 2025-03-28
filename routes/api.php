<?php

use Illuminate\Http\Request;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DemandeVoyageController;
use App\Http\Controllers\API\DocumentController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\UtilisateurController;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('login', [AuthController::class, 'login']);

// Routes protégées par sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
    
    // Utilisateurs
    Route::get('profile', [UserController::class, 'getProfile']);
    Route::apiResource('utilisateurs', UserController::class);
    
    // Demandes de voyage
    Route::apiResource('demandes-voyage', DemandeVoyageController::class);
    
    // Documents
    Route::post('documents', [DocumentController::class, 'store']);
    Route::get('documents/{id}', [DocumentController::class, 'show']);
    Route::get('documents/{id}/download', [DocumentController::class, 'download']);
    Route::delete('documents/{id}', [DocumentController::class, 'destroy']);
    
    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::put('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});