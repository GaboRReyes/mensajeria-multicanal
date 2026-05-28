<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Recalcula y persiste las estadísticas de una campaña.
 * Se dispara desde los Jobs de envío al terminar.
 */
class UpdateCampaignStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $campaignId) {}

    public function handle(): void
    {
        $campaign = Campaign::find($this->campaignId);
        if (! $campaign) return;

        $stats = Message::where('campaign_id', $this->campaignId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'enviado'    THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'entregado'  THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = 'fallido'    THEN 1 ELSE 0 END) as failed
            ")
            ->first();

        $total     = $stats->total ?? 0;
        $sent      = ($stats->sent ?? 0) + ($stats->delivered ?? 0);
        $delivered = $stats->delivered ?? 0;
        $failed    = $stats->failed ?? 0;

        $allProcessed = ($sent + $failed) >= $total && $total > 0;

        $campaign->update([
            'sent_count'      => $sent,
            'delivered_count' => $delivered,
            'failed_count'    => $failed,
            'status'          => $allProcessed ? Campaign::STATUS_COMPLETED : $campaign->status,
            'completed_at'    => $allProcessed ? now() : $campaign->completed_at,
        ]);
    }
}
