<?php

use App\Http\Controllers\SwitchUnitController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('context/switch-unit', SwitchUnitController::class)
    ->middleware('auth')
    ->name('context.switch-unit');
