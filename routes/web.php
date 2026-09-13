<?php

use App\Http\Actions\GenerateMenuPdfAction;
use App\Http\Actions\LoginAction;
use App\Http\Actions\LogoutAction;
use App\Http\Actions\RegisterAction;
use App\Http\Actions\UploadImageAction;
use App\Http\Actions\CheckImageStatusAction;
use App\Http\Actions\GetUserImagesAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/images/{image}/status', CheckImageStatusAction::class);
Route::get('/api/images', GetUserImagesAction::class);

// 인증 라우트
Route::post('/api/auth/register', RegisterAction::class);
Route::post('/api/auth/login', LoginAction::class);
Route::post('/api/auth/logout', LogoutAction::class);
Route::get('/api/auth/me', function () {
    if (Auth::check()) {
        $user = Auth::user();
        return response()->json([
            'logged_in' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'credits' => $user->credits,
            ]
        ]);
    }
    return response()->json(['logged_in' => false]);
});

// 이미지 업로드 및 AI 합성 라우트 (웹 세션 적용)
Route::post('/api/images/upload', UploadImageAction::class);

// PDF 임시 라우트 (클래스 에러 방지용 클로저)
Route::get('/api/menu-boards/pdf', function () {
    return response()->json(['message' => 'PDF 기능 준비 중입니다.']);
});