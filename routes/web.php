<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\DocumentController;

Route::get('/', [SearchController::class, 'index'])->name('search.index');
Route::post('/search', [SearchController::class, 'search'])->name('search.search');
Route::get('/upload', [DocumentController::class, 'index'])->name('documents.index');
Route::post('/upload', [DocumentController::class, 'upload'])->name('documents.upload');
