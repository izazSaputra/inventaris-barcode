<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/api/barcode', function (Request $request) {
    $request->validate([
        'kode' => 'required|string'
    ]);

    Log::info('Barcode received: ' . $request->kode);
    
    return response()->json([
        'status' => 'success',
        'message' => 'Barcode berhasil dipindai',
        'kode' => $request->kode,
        'timestamp' => now()
    ]);
});

Route::post('/barcode', function (Request $request) {
    $request->validate([
        'kode' => 'required|string'
    ]);
    
    Log::info('Barcode received: ' . $request->kode);
    
    return response()->json([
        'status' => 'success',
        'message' => 'Barcode berhasil dipindai',
        'kode' => $request->kode,
        'timestamp' => now()
    ]);
});