<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['meta' => 'array', 'occurred_at' => 'immutable_datetime', 'received_at' => 'immutable_datetime', 'severity' => 'integer', 'count' => 'integer'];
    }
}
