<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowingItem extends Model
{
    protected $fillable = ['borrowing_id', 'component_id', 'kit_id', 'quantity', 'returned_quantity'];

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(Borrowing::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function kit(): BelongsTo
    {
        return $this->belongsTo(Kit::class);
    }

    public function outstandingQuantity(): int
    {
        return $this->quantity - $this->returned_quantity;
    }
}
