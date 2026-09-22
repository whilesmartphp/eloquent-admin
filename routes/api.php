<?php

use Illuminate\Support\Facades\Route;

$controller = config('admin.controller');
Route::get('metrics', [$controller, 'metrics']);
Route::get('offers', [$controller, 'offers']);
Route::post('offers/{provider}', [$controller, 'createOffer'])->middleware(config('admin.write_middleware', []));
Route::delete('offers/{provider}/{id}', [$controller, 'revokeOffer'])->middleware(config('admin.write_middleware', []));
Route::get('users', [$controller, 'users']);
Route::get('users/{id}', [$controller, 'user']);
Route::get('mail-templates', [$controller, 'templates']);
Route::get('mail-templates/{key}', [$controller, 'template']);
Route::put('mail-templates/{key}', [$controller, 'updateTemplate'])->middleware(config('admin.write_middleware', []));
Route::post('mail-templates/{key}/preview', [$controller, 'previewTemplate']);
