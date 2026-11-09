<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class LocaleController extends Controller
{
    public function switch(string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, config('t3al.locales'), true), 404);

        session(['locale' => $locale]);

        return Redirect::back();
    }
}
