<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPasskey extends Model
{
    protected $table = 'user_passkeys';

    protected $fillable = [
        'user_id',
        'credential_id',
        'public_key',
        'sign_count',
        'transports',
        'aaguid',
        'label',
        'last_used_at',
    ];

    protected $hidden = ['public_key'];

    protected $casts = [
        'sign_count' => 'integer',
    ];

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'last_used_at' => $this->last_used_at ? \Carbon\Carbon::parse($this->last_used_at)->toIso8601String() : null,
        ];
    }
}
