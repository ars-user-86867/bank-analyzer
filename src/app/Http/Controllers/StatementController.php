<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\StatementParser;

class StatementController extends Controller
{
    public function process(Request $request, StatementParser $service)
    {
        $request->validate([
            'statement' => 'required|file|mimes:pdf,xlsx,xls|max:10000',
            'bank' => 'required|string',
        ]);

        $file = $request->file('statement');
        $bank = $request->input('bank');

        try {
            $transactions = $service->parse($file, $bank);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Ошибка при чтении файла: ' . $e->getMessage()]);
        }

        $stats = $service->calculate_stats($transactions, $bank);
        return view('results', $stats);
    }
}
