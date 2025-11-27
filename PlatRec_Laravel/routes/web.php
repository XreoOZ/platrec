<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

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
        
    Route::get('/deteksi', function () {
        return view('deteksi');
    });

Route::post('/api/save-detection', [App\Http\Controllers\DetectionController::class, 'saveDetection']);

});

require __DIR__.'/auth.php';
