<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per reason code, so REVIEW/REJECT volume can be grouped and filtered
        // by reason with an index (spec §6: finding which stage causes most REVIEWs).
        // reason_code is a plain string, not a DB enum, so adding a code to
        // ai-service/standard/reason_codes.py doesn't require a migration here
        // (the tradeoff: the two lists can drift apart).
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
