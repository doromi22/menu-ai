<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 정규화 pivot 테이블: reason_code별 GROUP BY/필터링을 인덱스 쿼리로 처리하기 위함
        // (spec §6: "REVIEW의 62%가 segmentation 문제였다" 같은 병목 분석 목적).
        // reason_code는 DB enum이 아니라 plain string - ai-service/standard/reason_codes.py의
        // ReasonCode가 항목을 추가할 때마다 여기 마이그레이션이 필요하지 않도록 함
        // (대신 두 쪽의 목록이 벌어질 수 있으니 동기화에 유의).
        Schema::create('image_processing_reasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('image_id')->constrained()->cascadeOnDelete();
            $table->enum('stage', ['segmentation', 'validator']);
            $table->string('reason_code');
            $table->timestamps();

            $table->index(['reason_code', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_processing_reasons');
    }
};
