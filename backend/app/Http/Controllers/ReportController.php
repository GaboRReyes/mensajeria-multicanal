<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function kpis()
    {
        return response()->json([
            'messages' => 100
        ]);
    }

    public function export($format)
    {
        return response()->json([
            'format' => $format
        ]);
    }
}