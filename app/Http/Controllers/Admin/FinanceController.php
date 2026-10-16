<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use Illuminate\View\View;

class FinanceController extends Controller
{
    /**
     * Display the financial dashboard.
     */
    public function index(): View
    {
        $totalRevenue = FinancialTransaction::totalRevenue();
        $totalExpenses = FinancialTransaction::totalExpenses();
        $netBalance = FinancialTransaction::netBalance();

        $recentTransactions = FinancialTransaction::query()
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.finance.index', [
            'totalRevenue' => $totalRevenue,
            'totalExpenses' => $totalExpenses,
            'netBalance' => $netBalance,
            'recentTransactions' => $recentTransactions,
        ]);
    }
}
