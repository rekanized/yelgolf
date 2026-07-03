<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hole extends Model
{
    protected $fillable = [
        'course_id',
        'udisc_hole_id',
        'layout_id',
        'layout_name',
        'layout_difficulty',
        'layout_order',
        'sort_order',
        'number',
        'hole_label',
        'par',
        'distance_meters',
        'distance_feet',
    ];

    protected function casts(): array
    {
        return [
            'layout_id' => 'integer',
            'layout_order' => 'integer',
            'sort_order' => 'integer',
            'number' => 'integer',
            'par' => 'integer',
            'distance_meters' => 'float',
            'distance_feet' => 'float',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}