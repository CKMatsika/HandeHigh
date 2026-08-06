<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AIFinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AIFinanceController extends Controller
{
    public function __construct(private AIFinanceService $aiFinance)
    {
    }

    public function dashboard()
    {
        $school = Auth::user()?->school;
        if (!$school) {
            abort(403);
        }

        $schoolId = $school->id;

        $healthScore = $this->aiFinance->getFinancialHealthScore($schoolId);
        $anomalies = $this->aiFinance->detectAnomalies($schoolId);
        $forecast = $this->aiFinance->forecastCashFlow($schoolId);
        $insights = $this->aiFinance->getSmartInsights($schoolId);
        $recommendations = $this->aiFinance->getBudgetRecommendations($schoolId);
        $revenueBreakdown = $this->aiFinance->getRevenueBreakdown($schoolId);
        $expenseBreakdown = $this->aiFinance->getExpenseBreakdown($schoolId);
        $metrics = $this->aiFinance->getKeyMetrics($schoolId);

        return view('admin.finance.ai-dashboard', compact(
            'healthScore',
            'anomalies',
            'forecast',
            'insights',
            'recommendations',
            'revenueBreakdown',
            'expenseBreakdown',
            'metrics'
        ));
    }

    public function healthScore()
    {
        $school = Auth::user()?->school;
        if (!$school) {
            abort(403);
        }

        return response()->json($this->aiFinance->getFinancialHealthScore($school->id));
    }

    public function anomalies()
    {
        $school = Auth::user()?->school;
        if (!$school) {
            abort(403);
        }

        return response()->json($this->aiFinance->detectAnomalies($school->id));
    }

    public function forecast()
    {
        $school = Auth::user()?->school;
        if (!$school) {
            abort(403);
        }

        return response()->json($this->aiFinance->forecastCashFlow($school->id));
    }

    public function insights()
    {
        $school = Auth::user()?->school;
        if (!$school) {
            abort(403);
        }

        return response()->json($this->aiFinance->getSmartInsights($school->id));
    }
}
