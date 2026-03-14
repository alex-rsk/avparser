<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function getBaseReport(Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(401, 'This download link has expired or is invalid.');
        }

        $request->validate([
            'search_query_id' => ['required', 'integer', 'exists:search_queries,id'],
            'date_from'       => ['required', 'date'],
            'date_to'         => ['required', 'date', 'after_or_equal:date_from'],
        ]);

        Log::channel('daily')->debug($request->all());
        
        $rs = new ReportService();

        $filename  = $rs->baseReport($request->date_from, $request->date_to, $request->search_query_id);
        Log::channel('daily')->debug($filename);
        return response()->json(["filename" => $filename], 200);
    }
}
