<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('images', function (Blueprint $table) {
            // ai-service/docs/standard-metadata.schema.json 응답의 스칼라 필드.
            // reason 배열(segmentation_reasons / validator_reasons)은
            // image_processing_reasons pivot 테이블로 정규화.
            $table->string('pipeline_version')->nullable()->after('status');
            $table->string('policy_version')->nullable()->after('pipeline_version');
            $table->string('policy_hash')->nullable()->after('policy_version');
            $table->string('template_version')->nullable()->after('policy_hash');
            $table->enum('mode', ['standard', 'premium'])->default('premium')->after('template_version');
            $table->enum('segmentation_status', ['PASS', 'REVIEW', 'REJECT'])->nullable()->after('mode');
            $table->enum('validator_status', ['PASS', 'REVIEW', 'REJECT'])->nullable()->after('segmentation_status');
            $table->enum('merchant_review', ['PENDING', 'APPROVED', 'REJECTED'])->nullable()->after('validator_status');
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn([
                'pipeline_version',
                'policy_version',
                'policy_hash',
                'template_version',
                'mode',
                'segmentation_status',
                'validator_status',
                'merchant_review',
            ]);
        });
    }
};
