<?php

use App\Http\Actions\CheckImageStatusAction;
use App\Http\Actions\GenerateMenuPdfAction;
use App\Http\Actions\GetUserImagesAction;
use App\Http\Actions\LoginAction;
use App\Http\Actions\LogoutAction;
use App\Http\Actions\MeAction;
use App\Http\Actions\RegisterAction;
use App\Http\Actions\UploadImageAction;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// JSON endpoints for the single-page UI. They live in the web group (not
// routes/api.php) because authentication is the browser session.
Route::prefix('api')->group(function () {
    Route::post('auth/register', RegisterAction::class);
    Route::post('auth/login', LoginAction::class);
    Route::post('auth/logout', LogoutAction::class);
    Route::get('auth/me', MeAction::class);

    Route::post('images/upload', UploadImageAction::class);
    Route::get('images', GetUserImagesAction::class);
    Route::get('images/{image}/status', CheckImageStatusAction::class);

    Route::get('menu-boards/pdf', GenerateMenuPdfAction::class);
});
