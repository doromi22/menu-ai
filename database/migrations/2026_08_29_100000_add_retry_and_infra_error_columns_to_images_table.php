<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // §11-4: ai-service/docs/standard-metadata.schema.json gained
        // is_infra_error and retry_attempts_used - added here so Laravel
        // can store the full response, not just the original §8 fields.
        Schema::table('images', function (Blueprint $table) {
            $table->boolean('is_infra_error')->default(false)->after('merchant_review');
            $table->unsignedTinyInteger('retry_attempts_used')->default(0)->after('is_infra_error');
        });
    }

    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            $table->dropColumn(['is_infra_error', 'retry_attempts_used']);
        });
    }
};
