<?php

use App\Http\Controllers\XlsxImportController;
use Illuminate\Support\Facades\Route;

Route::get('/',        [XlsxImportController::class, 'index'])->name('xlsx.index');
Route::post('/upload', [XlsxImportController::class, 'upload'])->name('xlsx.upload');