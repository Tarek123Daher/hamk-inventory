<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Kit extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'location',
    ];

    public function components(): BelongsToMany
    {
        return $this->belongsToMany(Component::class)
            ->withPivot('quantity', 'notes')
            ->withTimestamps();
    }
}
