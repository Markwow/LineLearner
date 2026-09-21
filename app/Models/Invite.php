<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invite extends Model
{
    protected $fillable = ['code', 'email', 'note', 'created_by', 'used_by', 'used_at', 'expires_at'];

    protected function casts(): array
    {
        return [
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function redeemer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    /**
     * A short, unambiguous code — no vowels or look-alike characters, so it can
     * be read aloud or typed without confusion.
     */
    public static function generateCode(): string
    {
        do {
            $code = strtoupper(Str::random(4).'-'.Str::random(4));
            $code = str_replace(['0', 'O', '1', 'I', 'L'], ['2', '3', '4', '5', '6'], $code);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isRedeemable(): bool
    {
        return ! $this->isUsed() && ! $this->isExpired();
    }

    public function status(): string
    {
        if ($this->isUsed()) {
            return 'used';
        }

        return $this->isExpired() ? 'expired' : 'open';
    }

    public function signupUrl(): string
    {
        return route('register', ['code' => $this->code]);
    }
}
