<?php

namespace App\Console\Commands;

use App\Model\Upload;
use App\Models\Statement;
use Illuminate\Support\Str;
use Illuminate\Console\Command;

class ReverseS3PathToFilePath extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ReverseS3PathToFilePath:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reverse S3 path to file path in statement table.';

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
        $textReplacedWith = env('SHORT_CODE_BASE_PATH');
        $textToBeSearched = env('AWS_URL');

        if (!empty($textToBeSearched) && !empty($textReplacedWith)) {
            $statementRecords = Statement::where('value', 'like', '%' . $textToBeSearched . '%')
                ->get();
            $updateRecored = 0;

            if ($statementRecords) {
                foreach ($statementRecords as $val) {
                    if (!empty($val->value)) {
                        $val->value = Str::replace($textToBeSearched, $textReplacedWith, $val->value);
                    }
                    if (!empty($val->note)) {
                        $val->note = Str::replace($textToBeSearched, $textReplacedWith, $val->note);
                    }
                    $val->save();
                    $updateRecored++;
                }
            }
            echo "Total record found for update: " . $statementRecords->count() . "\r\n";
            echo "Updated record: " . $updateRecored . "\r\n";
        } else {
            echo "ENV variables are blank please check once.";
        }
    }
}
