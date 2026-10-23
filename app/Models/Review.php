<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected'];

    protected $fillable = [
        'product_id', 'product_title', 'author_name', 'author_email',
        'rating', 'title', 'body', 'status',
    ];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
