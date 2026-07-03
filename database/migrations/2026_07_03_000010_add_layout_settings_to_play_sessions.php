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
            $table->unsignedBigInteger('host_layout_id')->nullable()->after('host_name');
        });

        Schema::table('play_session_user', function (Blueprint $table) {
            $table->unsignedBigInteger('selected_layout_id')->nullable()->after('joined_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('play_session_user', function (Blueprint $table) {
            $table->dropColumn('selected_layout_id');
        });

        Schema::table('play_sessions', function (Blueprint $table) {
            $table->dropColumn('host_layout_id');
        });
    }
};