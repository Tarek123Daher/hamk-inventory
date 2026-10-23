<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Borrowing extends Model
{
    protected $fillable = [
        'borrower_name', 'borrower_email', 'user_id', 'borrowed_at',
        'expected_return_at', 'returned_at', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'date',
            'expected_return_at' => 'date',
            'returned_at' => 'date',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(BorrowingItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
