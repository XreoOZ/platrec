<?php

use App\Http\Controllers\PlateDetectionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/python-health', [PlateDetectionController::class, 'checkPythonAPI']);
Route::post('/detect-plate', [PlateDetectionController::class, 'detectPlate']);