<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('image_id')->nullable()->constrained()->onDelete('set null'); // 합성 사진 매칭
            $table->string('name');              // 메뉴 이름 (예: 특제 라멘)
            $table->integer('price');            // 가격 (엔화 기준)
            $table->text('description')->nullable(); // 메뉴 설명
            $table->string('category')->nullable();   // 카테고리 (예: 메인, 음료)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};