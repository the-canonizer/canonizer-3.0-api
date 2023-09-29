<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use JeroenG\Explorer\Application\Explored;
use Laravel\Scout\Searchable;

class ElasticSearch extends Model implements Explored
{
   
    use Searchable;
 
    public function mappableAs(): array
    {
        return [
        	'id'=>$this->Id,
        	'title' => $this->title,
        ];
    }
}
