<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title');            // 메뉴판 제목 (예: 2026 봄 신메뉴판)
            $table->string('template_type');     // 템플릿 종류 (예: japanese_wood, modern_cafe)
            $table->string('paper_size')->default('A4'); // A4, B5 등
            $table->json('layout_data')->nullable();     // 메뉴 배치 정보 (JSON)
            $table->string('pdf_path')->nullable();      // 최종 생성된 인쇄용 PDF 경로
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_boards');
    }
};