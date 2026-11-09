<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

$marketingDomain = config('t3al.domains.marketing');

Route::domain($marketingDomain ?: null)->group(function () {
    Route::view('/', 'welcome')->name('home');
    Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');
});
