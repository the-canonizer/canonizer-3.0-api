<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CampUserRestriction;
use App\Models\CampRestrictionLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ExpireCampUserRestriction extends Command
{
    protected $signature = 'camp:expire-user-restrictions';

    protected $description = 'Expire camp user restrictions whose end time has passed';

    public function handle()
    {
        $now = Carbon::now();

        // Fetch active restrictions that should expire
        $restrictions = CampUserRestriction::where('status', 'active')
            ->whereNotNull('end_time')
            ->where('end_time', '<=', $now)
            ->get();

        if ($restrictions->isEmpty()) {
            $this->info('No restrictions to expire.');
            return Command::SUCCESS;
        }

        DB::transaction(function () use ($restrictions, $now) {

            foreach ($restrictions as $restriction) {

                // Update restriction status
                $restriction->status = 'expired';
                $restriction->save();

                // Log the expiration
                CampRestrictionLog::create([
                    'restriction_id' => $restriction->id,
                    'action'         => 'expired',
                    'performed_by'   => null, // system action
                    'notes'          => 'Restriction automatically expired by scheduler.',
                    'created_at'     => $now,
                ]);
            }
        });

        $this->info("Expired {$restrictions->count()} restriction(s).");

        return Command::SUCCESS;
    }
}
