<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'points-mall-shop',
        'status' => 'ok',
    ]);
});
