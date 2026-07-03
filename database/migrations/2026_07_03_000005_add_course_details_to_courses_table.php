<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->string('target_type')->nullable();
            $table->json('tee_types')->nullable();
            $table->json('land_types')->nullable();
            $table->string('property_type')->nullable();
            $table->json('difficulty_levels')->nullable();
            $table->boolean('has_bathroom')->nullable();
            $table->boolean('has_drinking_water')->nullable();
            $table->boolean('is_cart_friendly')->nullable();
            $table->boolean('is_dog_friendly')->nullable();
            $table->boolean('is_stroller_friendly')->nullable();
            $table->string('accessibility')->nullable();
            $table->text('accessibility_description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table): void {
            $table->dropColumn([
                'target_type',
                'tee_types',
                'land_types',
                'property_type',
                'difficulty_levels',
                'has_bathroom',
                'has_drinking_water',
                'is_cart_friendly',
                'is_dog_friendly',
                'is_stroller_friendly',
                'accessibility',
                'accessibility_description',
            ]);
        });
    }
};