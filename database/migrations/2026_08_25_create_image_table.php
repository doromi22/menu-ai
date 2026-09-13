<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // 개발 초기 테스트를 위해 nullable 지정
            $table->string('original_path');   // 원본 업로드 사진 경로
            $table->string('processed_path')->nullable(); // AI 배경 합성 후 사진 경로
            $table->string('prompt')->nullable();         // 합성 시 적용한 프롬프트
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};