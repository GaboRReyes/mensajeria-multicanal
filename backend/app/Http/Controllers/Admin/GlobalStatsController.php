<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GlobalStatsController extends Controller
{
    public function index()
    {
        $totalUsers    = User::count();
        $activeUsers   = User::where('is_active', true)->count();
        $totalMessages = Message::count();
        $totalCampaigns= Campaign::count();

        $messagesByStatus = Message::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $messagesByChannel = Message::select('channel', DB::raw('count(*) as total'))
            ->groupBy('channel')
            ->pluck('total', 'channel');

        $usersByRole = User::select('role', DB::raw('count(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        $recentActivity = Message::select('user_id', DB::raw('count(*) as msg_count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('user_id')
            ->with('user:id,name,email')
            ->orderByDesc('msg_count')
            ->limit(10)
            ->get();

        $campaignsByStatus = Campaign::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'users' => [
                'total'  => $totalUsers,
                'active' => $activeUsers,
                'by_role'=> $usersByRole,
            ],
            'messages' => [
                'total'      => $totalMessages,
                'by_status'  => $messagesByStatus,
                'by_channel' => $messagesByChannel,
            ],
            'campaigns' => [
                'total'    => $totalCampaigns,
                'by_status'=> $campaignsByStatus,
            ],
            'recent_activity' => $recentActivity,
            'updated_at'      => now()->toIso8601String(),
        ]);
    }

    public function logs()
    {
        $logs = Message::with(['user:id,name,email', 'events'])
            ->latest()
            ->paginate(50);

        return response()->json($logs);
    }
}
