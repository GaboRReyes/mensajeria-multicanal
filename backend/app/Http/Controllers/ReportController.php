<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;

use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

use App\Exports\KpisExport;

class ReportController extends Controller
{
    public function kpis(Request $request)
    {
        $from = $request->date('from', now()->subDays(30));
        $to   = $request->date('to', now());

        return response()->json([

            'volumen' => Message::whereBetween('created_at', [$from, $to])
                ->selectRaw('channel, status, COUNT(*) as total')
                ->groupBy('channel', 'status')
                ->get(),

            'tasa_entrega' => Message::whereBetween('created_at', [$from, $to])
                ->selectRaw('
                    channel,
                    100.0 * SUM(
                        CASE WHEN delivered_at IS NOT NULL THEN 1 ELSE 0 END
                    ) /
                    NULLIF(
                        SUM(CASE WHEN sent_at IS NOT NULL THEN 1 ELSE 0 END),
                        0
                    ) as tasa
                ')
                ->groupBy('channel')
                ->get(),

            'tendencia' => Message::whereBetween('created_at', [$from, $to])
                ->selectRaw("
                    DATE(created_at) as dia,
                    channel,
                    COUNT(*) as total
                ")
                ->groupBy('dia', 'channel')
                ->orderBy('dia')
                ->get()

        ]);
    }

    public function export(string $format, Request $request)
    {
        $data = $this->kpis($request)->getData(true);

        return match ($format) {

            'pdf' => Pdf::loadView('reports.kpis', [
                    'data' => $data
                ])
                ->download('reporte-mensajeria.pdf'),

            'excel' => Excel::download(
                new KpisExport($data),
                'reporte-mensajeria.xlsx'
            ),

            default => abort(404),
        };
    }
}