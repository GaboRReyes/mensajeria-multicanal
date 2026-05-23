<?php

namespace App\Http\Controllers;

use App\Exports\KpisExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    private function gatherKpis(): array
    {
        $totalMessages = DB::table('messages')->count();
        $pendingMessages = DB::table('messages')
            ->whereIn('status', ['programado', 'encolado'])
            ->count();

        $statusCounts = DB::table('messages')
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $channelCounts = DB::table('messages')
            ->select('channel', DB::raw('count(*) as total'))
            ->groupBy('channel')
            ->pluck('total', 'channel')
            ->toArray();

        $activeTemplates = DB::table('templates')->count();

        $pendingList = DB::table('messages')
            ->whereIn('status', ['programado', 'encolado'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get([
                'id',
                'channel',
                'status',
                'recipient_masked',
                'scheduled_at',
                'created_at',
            ]);

        return [
            'total_messages' => $totalMessages,
            'pending_messages' => $pendingMessages,
            'status_counts' => $statusCounts,
            'channel_counts' => $channelCounts,
            'active_templates' => $activeTemplates,
            'pending_list' => $pendingList,
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    public function kpis()
    {
        return response()->json($this->gatherKpis());
    }

    public function export(string $format, Request $request)
    {
        $reportData = $this->gatherKpis();
        $kpis = [
            ['Métrica', 'Valor'],
            ['Total mensajes', $reportData['total_messages']],
            ['Mensajes pendientes', $reportData['pending_messages']],
            ['Plantillas activas', $reportData['active_templates']],
        ];

        foreach ($reportData['status_counts'] as $status => $count) {
            $kpis[] = ["Status: {$status}", $count];
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.kpis', [
                'reportData' => $reportData,
            ]);
            return $pdf->download('reportes-kpis.pdf');
        }

        if ($format === 'excel') {
            return Excel::download(new KpisExport($kpis), 'reportes-kpis.xlsx');
        }

        return response()->json(['error' => 'Formato inválido.'], 400);
    }
}