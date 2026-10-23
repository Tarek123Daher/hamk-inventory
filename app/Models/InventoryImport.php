<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryImport extends Model
{
    protected $fillable = [
        'user_id', 'filename', 'sha256', 'inserted', 'updated', 'skipped', 'errors', 'error_details',
    ];

    protected function casts(): array
    {
        return ['error_details' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
