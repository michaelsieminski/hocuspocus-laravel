<?php

use Hocuspocus\HocuspocusLaravel;

Route::post(config('hocuspocus-laravel.route'), [HocuspocusLaravel::class, 'handleWebhook']);
Route::post(config('hocuspocus-laravel.route').'/store', [HocuspocusLaravel::class, 'storeData']);
Route::get(config('hocuspocus-laravel.route').'/get', [HocuspocusLaravel::class, 'getData']);