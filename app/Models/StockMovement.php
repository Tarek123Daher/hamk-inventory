<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'component_id', 'user_id', 'type', 'quantity', 'source', 'destination', 'reason',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
