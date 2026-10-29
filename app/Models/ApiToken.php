<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'token',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function issue(User $user, string $name = 'flutter'): string
    {
        $plain = Str::random(64);

        static::create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => hash('sha256', $plain),
        ]);

        return $plain;
    }

    public static function findByPlainToken(?string $plain): ?self
    {
        if ($plain === null || $plain === '') {
            return null;
        }

        return static::query()->where('token', hash('sha256', $plain))->first();
    }
}
