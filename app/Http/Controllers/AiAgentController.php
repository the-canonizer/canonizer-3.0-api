<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Nickname;
use App\Helpers\ResponseInterface;

class AiAgentController extends Controller
{
    public function __construct(ResponseInterface $resProvider)
    {
        $this->resProvider = $resProvider;
    }

    /**
     * List all bots owned by the authenticated user
     */
    public function list(Request $request)
    {
        try {
            $user = $request->user();
            $bots = User::where('parent_user_id', $user->id)
                ->where('type', 'bot')
                ->select('id', 'first_name', 'last_name', 'email', 'type', 'status', 'is_active', 'parent_user_id')
                ->get();

            // Attach nicknames to each bot
            foreach ($bots as $bot) {
                $bot->nicknames = Nickname::where('user_id', $bot->id)
                    ->select('id', 'nick_name')
                    ->get();
            }

            return $this->resProvider->apiJsonResponse(200, 'Success', $bots, null);
        } catch (\Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), null, null);
        }
    }

    /**
     * Show a single bot
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $bot = User::where('id', $id)
                ->where('parent_user_id', $user->id)
                ->where('type', 'bot')
                ->first();

            if (!$bot) {
                return $this->resProvider->apiJsonResponse(404, 'Agent not found', null, null);
            }

            $bot->nicknames = Nickname::where('user_id', $bot->id)
                ->select('id', 'nick_name')
                ->get();

            return $this->resProvider->apiJsonResponse(200, 'Success', $bot, null);
        } catch (\Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), null, null);
        }
    }

    /**
     * Update a bot's name
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            $bot = User::where('id', $id)
                ->where('parent_user_id', $user->id)
                ->where('type', 'bot')
                ->first();

            if (!$bot) {
                return $this->resProvider->apiJsonResponse(404, 'Agent not found', null, null);
            }

            if ($request->has('first_name')) {
                $bot->first_name = $request->first_name;
            }
            if ($request->has('last_name')) {
                $bot->last_name = $request->last_name;
            }
            if ($request->has('password') && !empty($request->password)) {
                $bot->password = \Illuminate\Support\Facades\Hash::make($request->password);
            }
            $bot->save();

            return $this->resProvider->apiJsonResponse(200, 'Agent updated successfully', $bot, null);
        } catch (\Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), null, null);
        }
    }

    /**
     * Deactivate a bot
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $bot = User::where('id', $id)
                ->where('parent_user_id', $user->id)
                ->where('type', 'bot')
                ->first();

            if (!$bot) {
                return $this->resProvider->apiJsonResponse(404, 'Agent not found', null, null);
            }

            $bot->is_active = 0;
            $bot->save();

            return $this->resProvider->apiJsonResponse(200, 'Agent deactivated successfully', null, null);
        } catch (\Exception $e) {
            return $this->resProvider->apiJsonResponse(400, $e->getMessage(), null, null);
        }
    }
}
