<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class BaseModel extends Model
{
   /**
    * Specify $logName to make the model use another name than the default.
    */
   protected static $logName = 'BaseModel';
   /** 
    *   events will get logged automatically
    */
   protected static $recordEvents = ['deleted', 'created', 'updated'];
   /** 
    *   The attributes that need to be logged can be defined either by their name 
    *  or you can put in a wildcard '*' to log any attribute that has changed.
    */
   protected static $logAttributes = ['*'];

   /**
    * If you do not want to log every attribute in your $logAttributes variable,
    *  but only those that has actually changed after the update, you can use $logOnlyDirty
    */
   protected static $logOnlyDirty = true;

   use LogsActivity;

   /**
    * By default the package will log created, updated, deleted in the description of the activity. 
    * You can modify this text by overriding the getDescriptionForEvent function.
    */
   protected function getDescriptionForEvent(string $eventName): string
   {
        return "A record has been {$eventName}";
   }
}