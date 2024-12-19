<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialDataDeletionRequest extends Model
{
    protected $dateFormat = 'U';
    public $timestamps = true;

    protected $table = 'social_data_deletion_requests';

    protected $fillable = [
        'id',
        'provider',
        'provider_id',
        'status',
    ];

    protected $casts = [
        'id' => 'string',
    ];
    
}
