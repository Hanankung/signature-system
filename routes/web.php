<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/pdf', [PdfController::class, 'index']);         // หน้าอัปโหลด
Route::post('/pdf/sign', [PdfController::class, 'sign']);    // ประมวลผล
