<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/pdf', [PdfController::class, 'index']);         // หน้าอัปโหลด
// Route::post('/pdf/sign', [PdfController::class, 'sign']);    // ประมวลผล

Route::post('/pdf/upload', [PdfController::class, 'upload']);
Route::get('/pdf/preview/{id}', [PdfController::class, 'preview']);
Route::post('/pdf/save-markers/{id}', [PdfController::class, 'saveMarkers']);
Route::post('/pdf/sign/{id}', [PdfController::class, 'signDocument']);
