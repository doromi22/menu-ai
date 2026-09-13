<?php

namespace App\Http\Actions;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class GenerateMenuPdfAction extends Controller
{
    public function __invoke(Request $request)
    {
        $menus = Menu::with('image')->get();

        return response()->json([
            'message' => 'PDF 생성 템플릿 준비 중',
            'menus' => $menus
        ]);
    }
}