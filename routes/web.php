<?php

use App\Http\Controllers\CardController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

$marketingDomain = config('t3al.domains.marketing');

Route::domain($marketingDomain ?: null)->group(function () {
    Route::view('/', 'welcome')->name('home');
    Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');
});

// The customer's card page. Its URL carries the card's unguessable code.
Route::get('/c/{card}', [CardController::class, 'show'])->name('cards.show');
Route::get('/c/{card}/apple-wallet', [CardController::class, 'addToAppleWallet'])->name('cards.apple');
Route::get('/c/{card}/google-wallet', [CardController::class, 'addToGoogleWallet'])->name('cards.google');

Route::mobilePass();
