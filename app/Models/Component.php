<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Component extends Model
{
    protected $fillable = [
        'sku',
        'name',
        'category_id',
        'manufacturer',
        'model',
        'image_path',
        'datasheet_url',
        'attributes',
        'description',
        'quantity',
        'damaged_quantity',
        'lost_quantity',
        'reorder_threshold',
        'is_active',
        'verified_quantity',
        'verified_at',
        'verification_notes',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'is_active' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function kits(): BelongsToMany
    {
        return $this->belongsToMany(Kit::class)
            ->withPivot('quantity', 'notes')
            ->withTimestamps();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function borrowingItems(): HasMany
    {
        return $this->hasMany(BorrowingItem::class);
    }

    public function getBorrowedQuantityAttribute(): int
    {
        return (int) $this->borrowingItems()
            ->selectRaw('COALESCE(SUM(quantity - returned_quantity), 0) as outstanding')
            ->value('outstanding');
    }

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->quantity - $this->damaged_quantity - $this->lost_quantity - $this->borrowed_quantity);
    }
}
