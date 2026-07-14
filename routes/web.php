<?php

use App\Livewire\TrendsIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', TrendsIndex::class)->name('home');
