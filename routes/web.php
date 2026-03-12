<?php
use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/files');

Route::prefix('files')->name('files.')->group(function () {
    Route::get('/',                    [FileController::class, 'index'])->name('index');
    Route::post('/',                   [FileController::class, 'store'])->name('store');
    Route::get('/{file}/download',     [FileController::class, 'download'])->name('download');
    Route::delete('/{file}',           [FileController::class, 'destroy'])->name('destroy');
});
