<?php

use Illuminate\Support\Facades\Route;

// Frontend menangani semua halaman via React Router
// Web route ini hanya untuk health check
Route::get('/', function () {
    return response()->json([
        'app'     => 'GasNgalam API',
        'version' => '1.0',
        'status'  => 'running',
    ]);
});
