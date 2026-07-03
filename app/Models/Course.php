<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'udisc_url',
        'udisc_id',
        'photos',
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
        'location_name',
        'description',
        'holes_count',
        'rating',
        'ratings_count',
        'latitude',
        'longitude',
        'established_year',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'photos' => 'array',
            'tee_types' => 'array',
            'land_types' => 'array',
            'difficulty_levels' => 'array',
            'has_bathroom' => 'boolean',
            'has_drinking_water' => 'boolean',
            'is_cart_friendly' => 'boolean',
            'is_dog_friendly' => 'boolean',
            'is_stroller_friendly' => 'boolean',
            'holes_count' => 'integer',
            'ratings_count' => 'integer',
            'rating' => 'float',
            'latitude' => 'float',
            'longitude' => 'float',
            'established_year' => 'integer',
            'imported_at' => 'datetime',
        ];
    }

    public function holes(): HasMany
    {
        return $this->hasMany(Hole::class)
            ->orderBy('layout_order')
            ->orderBy('sort_order');
    }

    public function playSessions(): HasMany
    {
        return $this->hasMany(PlaySession::class);
    }
}