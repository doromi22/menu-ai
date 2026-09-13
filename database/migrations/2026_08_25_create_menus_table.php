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
            $table->foreignId('image_id')->nullable()->constrained()->onDelete('set null'); // processed photo for this item
            $table->string('name');              // e.g. "Special ramen"
            $table->integer('price');            // JPY
            $table->text('description')->nullable();
            $table->string('category')->nullable();   // e.g. main, drink
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};