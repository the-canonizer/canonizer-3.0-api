<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Library\wiki_parser\wikiParser as wikiParser;
use App\Models\Statement;
use Illuminate\Support\Str;

class UpdateParseStatement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:parsevalue {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update parsed value as a Html in Statement table';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $WikiParser = new wikiParser;
        $id = $this->argument('id') ?? NULL;
        try {
            // Check the argument of id with command / else use the all.
            if (!empty($id)) {
                $id =  $id;
                $statement = Statement::find($id);
                $updateRecored = 1;
                $WikiParser = new wikiParser;
                $statement->parsed_value = $WikiParser->parse($statement->value);
                $statement->update();
                echo "Total record found for update: " . $statement->count() . "\r\n";
                echo "Updated record: " . $updateRecored . "\r\n";
            } else {
                $statements = Statement::all();
                $updateRecored = 0;
                foreach($statements as $statement) { 
                    $WikiParser = new wikiParser;
                    $statement->parsed_value = $WikiParser->parse($statement->value);
                    $statement->update();
                    $updateRecored++;
                }
                echo "Total record found for update: " . $statements->count() . "\r\n";
                echo "Updated record: " . $updateRecored . "\r\n";
            }   
        } catch (Exception $e) {
            echo "Updated record error " . $e->getMessage() . "\r\n";
        }
    }
}
