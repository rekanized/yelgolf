<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('play_sessions', function (Blueprint $table): void {
            $table->unsignedSmallInteger('current_hole_index')->default(1)->after('ended_at');
        });

        Schema::create('play_session_scores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('play_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hole_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('hole_index');
            $table->unsignedSmallInteger('strokes');
            $table->timestamps();

            $table->unique(['play_session_id', 'user_id', 'hole_id']);
            $table->index(['play_session_id', 'hole_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('play_session_scores');

        Schema::table('play_sessions', function (Blueprint $table): void {
            $table->dropColumn('current_hole_index');
        });
    }
};
