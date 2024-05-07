<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\CommandHistory;
use App\Models\Statement;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Throwable;


class ReplaceBetaURLWithCanonizerURLForStatements extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'replace:betaurlwithproxyurlinstatements';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will find all of the beta.canonizer.com base URL for files in statements. 
        And will replace it with canonizer.com domain as base URL.';

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
        $commandHistory = (new CommandHistory())->create([
            'name' => $this->signature,
            'parameters' => [],
            'started_at' => Carbon::now()->timestamp,
        ]);

        try {

            $this->info('Start of replace:betaurlwithproxyurlinstatements command.');

            $statementRecords = Statement::where('value', 'like', '%https://beta.canonizer.com/files%')
            ->orwhere('value', 'like', '%https://beta.canonizer.com/files%')
            ->get();

            $updateRecored = 0;
            if ($statementRecords) {
                foreach ($statementRecords as $val) {
                    $val->value = Str::replace('https://beta.canonizer.com/files', env('SHORT_CODE_BASE_PATH'), $val->value);
                    $val->value = Str::replace('http://beta.canonizer.com/files', env('SHORT_CODE_BASE_PATH'), $val->value);
                    
                    $val->note = Str::replace('https://beta.canonizer.com/files', env('SHORT_CODE_BASE_PATH'), $val->note);
                    $val->note = Str::replace('http://beta.canonizer.com/files', env('SHORT_CODE_BASE_PATH'), $val->note);

                    $val->parsed_value = Str::replace('https://beta.canonizer.com/files', env('SHORT_CODE_BASE_PATH'), $val->parsed_value);
                    $val->parsed_value = Str::replace('http://beta.canonizer.com/files', env('SHORT_CODE_BASE_PATH'), $val->parsed_value);

                    $val->update();
                    $updateRecored++;
                }
            }
            echo "Total record found for update: " . $statementRecords->count() . "\r\n";
            echo "Updated record: " . $updateRecored . "\r\n";

            $this->info('End of replace:betaurlwithproxyurlinstatements command');

        } catch (Throwable $th) {
            $commandHistory->error_output = json_encode($th);
            $commandHistory->save();
        }

        $commandHistory->finished_at = Carbon::now()->timestamp;
        $commandHistory->save();
    }
}
