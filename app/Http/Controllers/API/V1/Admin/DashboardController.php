<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Investment;
use App\Models\Investor;

class DashboardController extends Controller
{
    public function allInvestors()
    {
        $investors=Investor::count();
        return response()->json([
            "status" => "success",
            'total' => $investors,
        ], 200);
    }
    public function allInvestments()
    {
        $investments=Investment::count();
        return response()->json([
            "status" => "success",
            'total' => $investments,
        ], 200);
    }
    public function OngoingInvestments()
    {
        $investments=Investment::whereIn('status', ['0', '1'])->count();
        return response()->json([
            "status" => "success",
            'total' => $investments,
        ], 200);
    }
    public function GetPayingAmount()
    {
        $investments=Investment::where('type', '1')->whereIn('status', ['0', '1'])->get();
        $totalamount=0;
        foreach ($investments as $investment) {
            $totalamount=$totalamount+intval($investment->return);
        }
        return response()->json([
            "status" => "success",
            'amount' => $totalamount,
        ], 200);
    }
    public function GetCollectedAmount()
    {
        $investments=Investment::get();
        $totalamount=0;
        foreach ($investments as $investment) {
            $totalamount=$totalamount+intval($investment->amountpaid);
        }
        return response()->json([
            "status" => "success",
            'amount' => $totalamount,
        ], 200);
    }
    public function GetReturnsAmount()
    {
        $investments=Investment::get();
        $totalamount=0;
        foreach ($investments as $investment) {
            $totalamount=$totalamount+intval($investment->amount_to_be_returned);
        }
        return response()->json([
            "status" => "success",
            'amount' => $totalamount,
        ], 200);
    }

    public function recentinvestments()
    {
        $investments = Investment::with('investmentOwner', 'nok', 'bank')
        ->where('status', '0')
        ->orderBY('id', 'desc')
        ->limit(10)
        ->get();
        return response()->json($investments, 200);
    }
    public function recentinvestors()
    {
        $investors = Investment::orderBY('id', 'desc')
        ->limit(10)
        ->get();
        return response()->json($investors, 200);
    }


}
