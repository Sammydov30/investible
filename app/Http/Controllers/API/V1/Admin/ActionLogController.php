<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActionLog;
use App\Models\PaymentHistory;
use Illuminate\Http\Request;

class ActionLogController extends Controller
{
    public function index(Request $request)
    {
        $result = ActionLog::where('id', '!=', '0');
        if (request()->input("admin")!=null) {
            $result->where('adminid', request()->input("admin"));
        }
        if (request()->input("module")!=null) {
            $result->where('actionmodule', request()->input("module"));
        }
        if (request()->input("action")!=null) {
            $result->where('action', request()->input("action"));
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

        $logs=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($logs, 200);
    }

}

