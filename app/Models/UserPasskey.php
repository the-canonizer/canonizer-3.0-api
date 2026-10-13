<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPasskey extends Model
{
    protected $table = 'user_passkeys';

    protected $fillable = [
        'user_id',
        'credential_id',
        'credential_id_hash',
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

    /** credential_id is stored whole (TEXT); the unique index and lookups use this digest. */
    public static function hashCredentialId(string $credentialId): string
    {
        return hash('sha256', $credentialId);
    }

    public function setCredentialIdAttribute($value)
    {
        $this->attributes['credential_id'] = $value;
        $this->attributes['credential_id_hash'] = self::hashCredentialId((string)$value);
    }

    public static function findByCredentialId(string $credentialId): ?self
    {
        return static::where('credential_id_hash', self::hashCredentialId($credentialId))->first();
    }

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
