<?php

use Illuminate\Support\Facades\Route;

// Vue SPA route - catch all
Route::middleware(['web'])
    ->prefix('unperm')
    ->group(function () {
        Route::get('/{any?}', function () {
            return view('unperm::spa');
        })->where('any', '.*')->name('unperm.spa');
    });

