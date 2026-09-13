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
            $table->string('title');            // e.g. "Spring 2026 menu"
            $table->string('template_type');     // e.g. japanese_wood, modern_cafe
            $table->string('paper_size')->default('A4'); // A4, B5, ...
            $table->json('layout_data')->nullable();     // item placement
            $table->string('pdf_path')->nullable();      // generated print-ready PDF
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_boards');
    }
};