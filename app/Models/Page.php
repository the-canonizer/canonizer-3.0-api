<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $table = 'pages';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at', 'updated_at',
    ];

    /**
     * Relationships
     */

    /**
     * Get ads list for the specific page.
     */
    public function ads()
    {
        return $this->hasMany(Ads::class);
    }
    
    /**
     * Get images list for the specific page.
     */
    public function images()
    {
        return $this->hasMany(Image::class);
    }
}
