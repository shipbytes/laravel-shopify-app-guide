<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookDelivery extends Model
{
    public $timestamps = false;

    protected $fillable = ['webhook_id', 'topic', 'shop', 'received_at'];

    protected function casts(): array
    {
        return ['received_at' => 'datetime'];
    }
}
