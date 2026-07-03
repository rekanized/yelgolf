<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('udisc_hole_id')->nullable();
            $table->unsignedInteger('layout_id')->nullable();
            $table->string('layout_name')->nullable();
            $table->string('layout_difficulty')->nullable();
            $table->unsignedSmallInteger('layout_order')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->unsignedSmallInteger('number')->nullable();
            $table->string('hole_label')->nullable();
            $table->unsignedSmallInteger('par')->nullable();
            $table->decimal('distance_meters', 8, 2)->nullable();
            $table->decimal('distance_feet', 8, 2)->nullable();
            $table->timestamps();

            $table->index(['course_id', 'layout_order', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holes');
    }
};