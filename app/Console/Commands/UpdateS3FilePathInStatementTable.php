<?php

namespace App\Console\Commands;

use App\Model\Upload;
use App\Models\Statement;
use Illuminate\Support\Str;
use Illuminate\Console\Command;

class UpdateS3FilePathInStatementTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'FilePathToS3Path:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update file path in value column of Statement table.';

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
        $statementRecords = Statement::where('value', 'like', '%https://canonizer.com/files%')
            ->orwhere('value', 'like', '%http://canonizer.com/files%')
            ->get();
        $updateRecored = 0;
        if ($statementRecords) {
            foreach ($statementRecords as $val) {
                $val->value = Str::replace('http://canonizer.com/files', env('AWS_URL'), $val->value);
                $val->value = Str::replace('https://canonizer.com/files', env('AWS_URL'), $val->value);
                $val->note = Str::replace('http://canonizer.com/files', env('AWS_URL'), $val->note);
                $val->note = Str::replace('https://canonizer.com/files', env('AWS_URL'), $val->note);
                $val->update();
                $updateRecored++;
            }
        }
        echo "Total record found for update: " . $statementRecords->count() . "\r\n";
        echo "Updated record: " . $updateRecored . "\r\n";
    }
}
