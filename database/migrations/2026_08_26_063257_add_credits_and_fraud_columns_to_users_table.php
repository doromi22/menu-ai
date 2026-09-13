<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('credits')->default(0)->after('password');
            $table->string('signup_ip', 45)->nullable()->after('credits');
            $table->string('device_fingerprint')->nullable()->after('signup_ip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['credits', 'signup_ip', 'device_fingerprint']);
        });
    }
};