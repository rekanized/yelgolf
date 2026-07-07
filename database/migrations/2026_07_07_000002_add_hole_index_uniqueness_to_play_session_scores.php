<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('play_session_scores', function (Blueprint $table): void {
            $table->unique(
                ['play_session_id', 'user_id', 'hole_index'],
                'play_session_scores_session_user_hole_index_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::table('play_session_scores', function (Blueprint $table): void {
            $table->dropUnique('play_session_scores_session_user_hole_index_unique');
        });
    }
};
