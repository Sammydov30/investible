<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investor\CreateInvestorRequest;
use App\Http\Requests\Investor\UpdateInvestorRequest;
use App\Models\Investor;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InvestorController extends Controller
{
    public function index(Request $request)
    {
        // $result = Investor::with('investments');
        $result = Investor::where('id', '!=', '0');
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->where('firstname', "like", "%{$search}%")
            ->orWhere('lastname', "like", "%{$search}%")
            ->orWhere('othername', "like", "%{$search}%")
            ->orWhere('email', "like", "%{$search}%");
        }
        if (request()->input("codenumber")!=null) {
            $result->where('codenumber', request()->input("codenumber"));
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
            $perPage=10;
        }

        $patients=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        //$patients=$result->orderBY($sortBy, $sortOrder)->get();

        // $dataset = array(
        //     "echo" => 1,
        //     "totalrecords" => count($patients),
        //     "totaldisplayrecords" => count($patients),
        //     "data" => $patients
        // );

        return response()->json($patients, 200);
    }

    public function store(CreateInvestorRequest $request)
    {
        $investor=Investor::create([
            'codenumber' => $this->getInvestorNO(),
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'othername' => $request->othername,
            'phonenumber' => $request->phonenumber,
            'email' => $request->email,
            'address' => $request->address,
            'status' => '0',
        ]);
        return response()->json([
            "message"=>"Investor Added Successfully",
            "status" => "success",
            'investor' => $investor,
        ], 200);
    }

    public function show($investor)
    {
        $investor=Investor::find($investor);
        if (!$investor) {
            return response()->json(["message"=>"This record doesn't exist", "status"=>"error"], 400);
        }
        return response()->json([
            "message"=>"Investor Fetched Successfully",
            "status" => "success",
            'investor' => $investor,
        ], 200);
    }

    public function update(UpdateInvestorRequest $request, Investor $investor)
    {
        $investor->update([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'othername' => $request->othername,
            'phonenumber' => $request->phonenumber,
            'email' => $request->email,
            'address' => $request->address,
            'status' => '0',
        ]);
        return response()->json([
            "message"=>"Investor Added Successfully",
            "status" => "success",
            'investor' => $investor,
        ], 200);
    }

    public function destroy(Investor $investor)
    {
        $investor->delete();
        $response=[
            "message" => "Investor Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function getInvestorNO() {
        $i=0;
        while ( $i==0) {
          $investorno=rand(10000000, 99999999);
          $check=Investor::where('codenumber', $investorno)->first();
          if (!$check) {
            $i=1;
          }
        }
        return "GI".$investorno;
      }
}

