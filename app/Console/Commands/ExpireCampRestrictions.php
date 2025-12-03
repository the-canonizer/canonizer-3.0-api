<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CampUserRestriction;
use App\Models\CampRestrictionLog;
use Carbon\Carbon;

class ExpireCampRestrictions extends Command
{
    protected $signature = 'camp:expire-restrictions';
    protected $description = 'Expire camp user restrictions whose end_time has passed';

    public function handle()
    {
        $now = Carbon::now();

        $toExpire = CampUserRestriction::where('status', 'active')
            ->where('end_time', '<=', $now)
            ->get();

        foreach ($toExpire as $restriction) {
            $restriction->update(['status' => 'expired']);
            CampRestrictionLog::create([
                'restriction_id' => $restriction->id,
                'action' => 'expired',
                'performed_by' => null,
                'notes' => 'Expired automatically by scheduler',
                'created_at' => Carbon::now()
            ]);
            // Optionally notify user about expiry
            // $restriction->user->notify(new \App\Notifications\UserRestrictionExpiredNotification($restriction->camp, $restriction));
        }

        $this->info('Expired ' . $toExpire->count() . ' restrictions.');
        return 0;
    }
}
