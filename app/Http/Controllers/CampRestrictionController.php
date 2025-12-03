<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CampUserRestriction;
use App\Models\CampRestrictionLog;
use App\Models\Camp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException; 
use App\Notifications\UserRestrictedNotification;
use Carbon\Carbon;

class CampRestrictionController extends Controller
{
    public function index(Request $request, Camp $camp)
    {        
        $perPage = $request->get('per_page', 25);
        $data = CampUserRestriction::with(['user','leader'])
            ->where('camp_id', $camp->id)
            ->orderBy('created_at','desc')
            ->paginate($perPage);

        return response()->json($data);
    }

    // restrict a user for 24 hours (or reset existing)
    public function restrict(Request $request, Camp $camp)
    {        
        $data = $request->validate([
            'restricted_user_id' => 'required|integer|exists:users,id',
            'reason' => 'required|string|max:2000',
            'duration_hours' => 'nullable|integer|min:1' // optional override
        ]);

        $duration = $data['duration_hours'] ?? 24;
        $restrictedUserId = $data['restricted_user_id'];

        DB::beginTransaction();
        try {
            // find active restriction for this user & camp
            $restriction = CampUserRestriction::where('camp_id', $camp->id)
                ->where('restricted_user_id', $restrictedUserId)
                ->where('status', 'active')
                ->first();

            $now = Carbon::now();
            $endTime = $now->copy()->addHours($duration);

            if ($restriction) {
                // reset start_time and end_time => extension as per requirements
                $restriction->update([
                    'start_time' => $now,
                    'end_time' => $endTime,
                    'reason' => $data['reason'],
                ]);
                $logAction = 'extended';
            } else {
                $restriction = CampUserRestriction::create([
                    'camp_id' => $camp->id,
                    'camp_num' => $camp->camp_num,
                    'topic_num' => $camp->topic_num,
                    'restricted_user_id' => $restrictedUserId,
                    'restricted_by' => $request->user()->id,
                    'reason' => $data['reason'],
                    'start_time' => $now,
                    'end_time' => $endTime,
                    'status' => 'active'
                ]);
                $logAction = 'restricted';
            }

            // create log
            CampRestrictionLog::create([
                'restriction_id' => $restriction->id,
                'action' => $logAction,
                'performed_by' => $request->user()->id,
                'notes' => $data['reason'],
                'created_at' => $now
            ]);

            // notify user (in-app and email)
            $user = User::find($restrictedUserId);
            $user->notify(new UserRestrictedNotification($camp, $restriction));

            DB::commit();

            return response()->json([
                'message' => 'User restricted successfully',
                'restriction' => $restriction
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // lift restriction
    public function lift(Request $request, Camp $camp, User $user)
    {        
        $restriction = CampUserRestriction::where('camp_id', $camp->id)
            ->where('restricted_user_id', $user->id)
            ->whereIn('status', ['active'])
            ->first();

        if (! $restriction) {
            return response()->json(['message' => 'No active restriction found'], 404);
        }

        $restriction->update(['status' => 'lifted', 'end_time' => Carbon::now()]);

        CampRestrictionLog::create([
            'restriction_id' => $restriction->id,
            'action' => 'lifted',
            'performed_by' => $request->user()->id,
            'notes' => 'Manually lifted by camp leader',
            'created_at' => Carbon::now()
        ]);

        // optional notification
        // $user->notify(new \App\Notifications\UserRestrictionLiftedNotification($camp, $restriction));

        return response()->json(['message' => 'Restriction lifted', 'restriction' => $restriction]);
    }

    // explicit extend/reset restriction (for repeated violation)
    public function extend(Request $request, Camp $camp, User $user)
    {        
        $data = $request->validate([
            'reason' => 'required|string|max:2000',
            'duration_hours' => 'nullable|integer|min:1'
        ]);
        $duration = $data['duration_hours'] ?? 24;

        $restriction = CampUserRestriction::where('camp_id', $camp->id)
            ->where('restricted_user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (! $restriction) {
            return response()->json(['message' => 'No active restriction to extend'], 404);
        }

        $restriction->update([
            'start_time' => Carbon::now(),
            'end_time' => Carbon::now()->addHours($duration),
            'reason' => $data['reason']
        ]);

        CampRestrictionLog::create([
            'restriction_id' => $restriction->id,
            'action' => 'extended',
            'performed_by' => $request->user()->id,
            'notes' => $data['reason'],
            'created_at' => Carbon::now()
        ]);

        $user->notify(new UserRestrictedNotification($camp, $restriction));

        return response()->json(['message' => 'Restriction extended', 'restriction' => $restriction]);
    }
}
