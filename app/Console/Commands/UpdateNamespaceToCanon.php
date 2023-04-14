<?php

namespace App\Console\Commands;

use App\Model\Upload;
use App\Models\Reply;
use App\Models\Statement;
use Illuminate\Support\Str;
use Illuminate\Console\Command;

class UpdateNamespaceToCanon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateNamespaceToCanon:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update namespace to canon in statement and post table.';

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
        $textReplacedWith = 'canon=';
        $textToBeSearched = 'namespace=';

        if (!empty($textToBeSearched) && !empty($textReplacedWith)) {
            $statementRecords = Statement::where('value', 'like', '%' . $textToBeSearched . '%')->get();

            $updateStatementRecored = 0;

            if ($statementRecords) {
                foreach ($statementRecords as $statementRecord) {
                    if (!empty($statementRecord->value)) {
                        $statementRecord->value = Str::replace($textToBeSearched, $textReplacedWith, $statementRecord->value);
                    }
                    if (!empty($statementRecord->parsed_value)) {
                        $statementRecord->parsed_value = Str::replace($textToBeSearched, $textReplacedWith, $statementRecord->parsed_value);
                    }
                    if (!empty($statementRecord->note)) {
                        $statementRecord->note = Str::replace($textToBeSearched, $textReplacedWith, $statementRecord->note);
                    }
                    $statementRecord->save();
                    $updateStatementRecored++;
                }
            }

            $postRecords = Reply::where('body', 'like', '%' . $textToBeSearched . '%')->get();
            $updatePostRecored = 0;
            if ($postRecords) {
                foreach ($postRecords as $postRecord) {
                    if (!empty($postRecord->body)) {
                        $postRecord->body = Str::replace($textToBeSearched, $textReplacedWith, $postRecord->body);
                    }
                    $postRecord->save();
                    $updatePostRecored++;
                }
            }
            echo "Total statement record found for update: " . $statementRecords->count() . "\r\n";
            echo "Updated statement record: " . $updateStatementRecored . "\r\n";
            echo "Total Post record found for update: " . $postRecords->count() . "\r\n";
            echo "Updated Post record: " . $updatePostRecored . "\r\n";
        } else {
            echo "Search and replace variables are blank please check once.";
        }
    }
}
