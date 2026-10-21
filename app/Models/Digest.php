<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Digest extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'window_start' => 'immutable_datetime', 'window_end' => 'immutable_datetime', 'sent_at' => 'immutable_datetime'];
    }
}
