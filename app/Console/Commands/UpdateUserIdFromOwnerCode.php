<?php

namespace App\Console\Commands;

use App\Models\CommandHistory;
use App\Models\Nickname;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateUserIdFromOwnerCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nicknames:populate-userid-from-ownercode';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will populate user_id from owner_code';

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
        $startTime = Carbon::now()->timestamp;

        $commandHistory = (new CommandHistory())->create([
            'name' => $this->signature,
            'parameters' => [],
            'started_at' => $startTime,
        ]);

        if (!Schema::hasColumn('nick_name', 'owner_code')) {
            $this->info('Command already executed.');
        } else {
    
            $this->newLine();
            $this->info('Mapping Owner code to user id...');
            $this->withProgressBar(Nickname::where('id', '>', 0)->get(), function (Nickname $nickname) {
                $nickname->user_id = intval(str_replace('Malia', '', base64_decode($nickname->owner_code)));
                $nickname->save();
            });
            $this->newLine();
    
            $this->newLine();
            $this->info('Dropping owner_code from schema...');
            $this->withProgressBar(1, function () {
                if (Schema::hasColumn('nick_name', 'owner_code')) {
                    Schema::table('nick_name', function (Blueprint $table) {
                        $table->foreign('user_id')->references('id')->on('person');
                        $table->dropColumn('owner_code');
                    });
                }
            });
            $this->newLine();
        }

        $endTime = Carbon::now()->timestamp;
        $commandHistory->finished_at = $endTime;
        $commandHistory->save();

        $this->info('Command executed in: ' . ($endTime - $startTime) . ' seconds');
    }
}
