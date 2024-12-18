<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialDataDeletionRequest extends Model
{
    protected $dateFormat = 'U';
    public $timestamps = false;
    protected $table = 'social_data_deletion_requests';

    protected $fillable = [
        'provider',
        'provider_id',
        'status',
    ];
}
