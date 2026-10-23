<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopSettings extends Model
{
    protected $fillable = ['auto_approve', 'widget_title'];

    protected $attributes = [
        'auto_approve' => false,
        'widget_title' => 'Customer reviews',
    ];

    protected function casts(): array
    {
        return ['auto_approve' => 'boolean'];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
