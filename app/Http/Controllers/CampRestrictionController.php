<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CampUserRestriction;
use App\Models\CampRestrictionLog;
use App\Models\Camp;
use App\Models\Nickname;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException; 
use Illuminate\Support\Facades\Validator;

use App\Notifications\UserRestrictedNotification;
use Carbon\Carbon;

class CampRestrictionController extends Controller
{
    public function index(Request $request, $id)
    {        
        $camp = Camp::findOrFail($id);
        $perPage = $request->get('per_page', 25);
        $data = CampUserRestriction::with(['user','leader'])
            ->where('camp_id', $camp->id)
            ->orderBy('created_at','desc')
            ->paginate($perPage);

        return response()->json($data);
    }

    // restrict a user for 24 hours (or reset existing)
    public function restrict(Request $request, $id)
    {        
        $validator = Validator::make($request->all(), [
            'restricted_user_nick_name_id' => 'required|integer|exists:nick_name,id',
            'reason' => 'required|string|max:2000',
            'duration_hours' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        $restricted_user_nick_name_id = $data['restricted_user_nick_name_id'];
        $nickName = Nickname::findOrFail($restricted_user_nick_name_id);
        $user_id = $nickName->user_id;
        
        $camp = Camp::findOrFail($id);
        $duration = $data['duration_hours'] ?? 24;

        DB::beginTransaction();
        try {
            // find active restriction for this user & camp
            $restriction = CampUserRestriction::where('camp_id', $camp->id)
                ->where('restricted_user_id', $user_id)
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
                    'restricted_user_id' => $user_id,
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
            $user = User::find($user_id);
            // $user->notify(new UserRestrictedNotification($camp, $restriction));

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
    public function lift(Request $request, $id, $user_id)
    {        

        $nickName = Nickname::findOrFail($user_id);
        $user_id = $nickName->user_id;
        $camp = Camp::findOrFail($id);
        $user = User::findOrFail($user_id);
        // dd($camp,$user);
        $restriction = CampUserRestriction::where('camp_id', $camp->id)
            ->where('topic_num','=',$camp->topic_num)
            ->where('camp_num','=',$camp->camp_num)
            ->where('restricted_user_id', $user->id)
            ->whereIn('status', ['active'])
            ->first();
        // dd($restriction);
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
    public function extend(Request $request, $id, $user_id)
    {        

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:2000',
            'duration_hours' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();


        $duration = $data['duration_hours'] ?? 24;

        $camp = Camp::findOrFail($id);
        $nickName = Nickname::findOrFail($user_id);
        $user_id = $nickName->user_id;
        $user = User::findOrFail($user_id); 
        
        $restriction = CampUserRestriction::where('camp_id', $camp->id)
            ->where('topic_num','=',$camp->topic_num)
            ->where('camp_num','=',$camp->camp_num)
            ->where('restricted_user_id', $user->id)
            ->whereIn('status', ['active'])
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

        // $user->notify(new UserRestrictedNotification($camp, $restriction));

        return response()->json(['message' => 'Restriction extended', 'restriction' => $restriction]);
    }

    public function restrictionLogs($id){
        $logs = CampRestrictionLog::with('restriction')->where('restriction_id','=',$id)->get();
        return response()->json(['message' => 'Restriction Logs', 'log' => $logs]);
 
    }
}
