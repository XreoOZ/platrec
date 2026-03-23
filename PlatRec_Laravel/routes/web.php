<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PlateDetectionController; // <-- TAMBAHIN INI

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    // INI YANG DIUBAH - PAKE CONTROLLER
    Route::get('/dashboard', function () {
        if (auth()->user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } else {
            return redirect()->route('staff.dashboard');
        }
    })->name('dashboard');

    Route::get('/admin/dashboard', [PlateDetectionController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/staff/dashboard', [PlateDetectionController::class, 'staffDashboard'])->name('staff.dashboard');

    Route::post('/detect-plate', function (Request $req) {
        $res = Http::attach(
            'file',
            file_get_contents($req->file('image')->path()),
            $req->file('image')->getClientOriginalName()
        )->post('http://127.0.0.1:5000/detect');

        return $res->json();
    });

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
    
    Route::put('/api/plates/{id}', [PlateDetectionController::class, 'updatePlate']);
    Route::get('/deteksi', [PlateDetectionController::class, 'deteksi'])->name('deteksi');
    Route::get('/riwayat', [PlateDetectionController::class, 'riwayat'])->name('riwayat');

    Route::post('/api/save-detection', [App\Http\Controllers\DetectionController::class, 'saveDetection']);

});

require __DIR__.'/auth.php';