<?php

use Illuminate\Support\Facades\Route;

Route::get('/gallery', function () {
    return 'Gallery works!';
})->name('gallery.index');
