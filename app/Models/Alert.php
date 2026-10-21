<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['notified_at' => 'immutable_datetime', 'severity' => 'integer', 'acknowledged_at' => 'immutable_datetime', 'resolved_at' => 'immutable_datetime'];
    }
}
