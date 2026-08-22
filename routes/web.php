<?php

use App\Http\Controllers\UserAchievementController;
use Illuminate\Support\Facades\Route;

Route::get('/', App\Http\Controllers\DashboardController::class);

Route::get('/users/{user}/achievements', UserAchievementController::class);
