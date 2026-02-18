<?php

use App\Http\Controllers\UserAchievementController;
use Illuminate\Support\Facades\Route;

Route::get('/users/{user}/achievements', UserAchievementController::class);
