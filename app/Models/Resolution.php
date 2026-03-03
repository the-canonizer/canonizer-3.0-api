<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Resolution extends Model
{
    use HasFactory;
    protected $table = 'resolutions';
    protected $fillable = ['title', 'resolution'];
    public $timestamps = true;

    protected $hidden = ['pivot', 'created_at', 'updated_at'];

    protected $guarded = [];

    public function videos() {
        return $this->belongsToMany(Video::class, 'video_resolutions', 'resolution_id', 'video_id')->as('videos');
    }
}
