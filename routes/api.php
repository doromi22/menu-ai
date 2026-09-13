<?php

use App\Http\Actions\UploadImageAction;
use Illuminate\Support\Facades\Route;

Route::post('/images/upload', UploadImageAction::class);