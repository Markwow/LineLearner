<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recording extends Model
{
    protected $fillable = ['script_id', 'line_index', 'path'];

    public function script(): BelongsTo
    {
        return $this->belongsTo(Script::class);
    }

    public function url(): string
    {
        // Root-relative so it resolves against whatever host/port serves the app.
        return '/storage/'.ltrim($this->path, '/');
    }
}
