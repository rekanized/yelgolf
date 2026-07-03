<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('play_sessions', function (Blueprint $table) {
            $table->string('host_session_key')->nullable()->after('host_id');
            $table->string('host_name')->nullable()->after('host_session_key');
            $table->foreignId('host_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('play_sessions', function (Blueprint $table) {
            $table->dropColumn(['host_session_key', 'host_name']);
            $table->foreignId('host_id')->nullable(false)->change();
        });
    }
};