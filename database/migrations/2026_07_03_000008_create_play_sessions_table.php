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
        Schema::create('play_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('host_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('host_session_key')->nullable();
            $table->string('host_name')->nullable();
            $table->unsignedBigInteger('host_layout_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedSmallInteger('current_hole_index')->default(1);
            $table->timestamps();
        });

        Schema::create('play_session_user', function (Blueprint $table) {
            $table->foreignId('play_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('invited');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->unsignedBigInteger('selected_layout_id')->nullable();
            $table->timestamps();

            $table->unique(['play_session_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('play_session_user');
        Schema::dropIfExists('play_sessions');
    }
};
