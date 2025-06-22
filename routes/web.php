<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LinkedInController;
use App\Http\Controllers\PersonaController;

Route::get('/linkedin', LinkedInController::class);

Route::get('/', PersonaController::class);
