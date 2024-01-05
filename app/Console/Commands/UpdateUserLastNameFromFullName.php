<?php

namespace App\Console\Commands;

use App\Facades\Util;
use App\Models\User;
use Illuminate\Console\Command;

class UpdateUserLastNameFromFullName extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:updateLastNameFromFullName';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will update last name of the user from full name if last name is empty.';

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
        //
        $userWithEmptyLastName = User::where('last_name', '')->orWhereNull('last_name')->get();
        foreach ($userWithEmptyLastName as $user) {
            $splitName = Util::split_name($user->first_name);
            if (count($splitName) > 1 && empty($user->last_name)) {
                $user->first_name = $splitName[0];
                $user->last_name = $splitName[1];
                $user->save();
            }
        }
    }
}
