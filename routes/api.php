<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/barcode', function (Request $request) {
    return response()->json([
        'status' => 'success',
        'kode' => $request->kode
    ]);
});
