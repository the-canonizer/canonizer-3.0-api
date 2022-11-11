<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Namespaces extends Model
{
    protected $table = 'namespace';
    public $timestamps = false;

    protected $guarded = [];

    public static function getNamespaceLabel($namespace,$label = ''){
        if(isset($label[0]) && $label[0] !='/'){
            $label = "/".$label;
        }
   
       if((!empty($label) && $label[strlen($label) - 1] != '/')){
           $label = $label."/";
       }
   
        if($namespace->parent_id != 0 && $namespace->id != $namespace->parent_id){
            $nameSpaceParent = self::find($namespace->parent_id);
            return self::getNamespaceLabel($nameSpaceParent,$nameSpaceParent->name.$label);
        }
   
        return $label;
    }
}
