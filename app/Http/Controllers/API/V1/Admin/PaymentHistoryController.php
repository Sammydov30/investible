<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentHistory;
use Illuminate\Http\Request;

class PaymentHistoryController extends Controller
{
    public function index(Request $request)
    {
        $result = PaymentHistory::with('investmentOwner', 'investment', 'bank');
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->whereHas('investmentOwner', function ($query) use($search)
            {
                $query->where('firstname', "like", "%{$search}%")
                ->orWhere('lastname', "like", "%{$search}%")
                ->orWhere('othername', "like", "%{$search}%");
            });
        }
        if (request()->input("transfercode") != null) {
            $search=request()->input("transfercode");
            $result->where('transfercode', "like", "%{$search}%");
        }
        if (request()->input("investor")!=null) {
            $result->where('investorid', request()->input("investor"));
        }
        if (request()->input("investment")!=null) {
            $result->where('investmentid', request()->input("investment"));
        }
        if ((request()->input("sortBy")!=null) && in_array(request()->input("sortBy"), ['id', 'created_at'])) {
            $sortBy=request()->input("sortBy");
        }else{
            $sortBy='id';
        }
        if ((request()->input("sortorder")!=null) && in_array(request()->input("sortorder"), ['asc', 'desc'])) {
            $sortOrder=request()->input("sortorder");
        }else{
            $sortOrder='desc';
        }
        if (!empty(request()->input("perpage"))) {
            $perPage=request()->input("perpage");
        } else {
            $perPage=100;
        }

        $histories=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($histories, 200);
    }
    public function GetPayedAmount(Request $request)
    {
        $date=$request->date;
        $payments=PaymentHistory::where('pdate', $date)->get();
        $totalamount=0;
        foreach ($payments as $payment) {
            $totalamount=$totalamount+intval($payment->amount);
        }
        return response()->json([
            "message"=>"Total Payment for ".$date,
            "status" => "success",
            'amount' => $totalamount,
        ], 200);
    }

}

