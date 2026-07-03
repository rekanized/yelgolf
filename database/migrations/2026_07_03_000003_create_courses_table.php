<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('udisc_url')->unique();
            $table->string('udisc_id')->nullable();
            $table->string('location_name')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('holes_count')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->unsignedInteger('ratings_count')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};