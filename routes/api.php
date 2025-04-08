<?php

use Illuminate\Http\Request;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DemandeVoyageController;
use App\Http\Controllers\API\DocumentController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\UserController;
use Illuminate\Support\Facades\Route;

// Routes publiques
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1'); // Limite à 5 tentatives par minute
Route::post('refresh', [AuthController::class, 'refresh'])->middleware('throttle:10,1'); // Placé hors auth pour permettre le rafraîchissement des tokens expirés
    
// Routes protégées par JWT
Route::middleware('auth:api')->group(function () {
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