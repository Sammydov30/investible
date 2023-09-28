<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NextOfKin\CreateNextOfKinRequest;
use App\Http\Requests\NextOfKin\UpdateNextOfKinRequest;
use App\Models\NextOfKin;
use App\Traits\ActionLogTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NextOfKinController extends Controller
{
    use ActionLogTrait;
    public function index(Request $request)
    {
        $result = DB::table('next_of_kin');

        if (request()->input("investor") != null) {
            $search=request()->input("investor");
            $result->where('investor', $search);
        }
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->where('firstname', "like", "%{$search}%")
            ->orWhere('lastname', "like", "%{$search}%")
            ->orWhere('othername', "like", "%{$search}%")
            ->orWhere('email', "like", "%{$search}%");
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

        $accounts=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($accounts, 200);
    }

    public function store(CreateNextOfKinRequest $request)
    {
        $nextofkin=NextOfKin::create([
            'investor' => $request->investor,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'othername' => $request->othername,
            'phone' => $request->phonenumber,
            'address' => $request->address,
            'relationship' => $request->relationship,
        ]);
        $details=json_encode($nextofkin);
        $this->AddLog($details, 'nextofkin', 'Created');
        return response()->json([
            "message"=>"Kin Added Successfully",
            "status" => "success",
            'nextofkin' => $nextofkin,
        ], 200);
    }

    public function show($nextofkin)
    {
        $nextofkin=NextOfKin::find($nextofkin);
        if (!$nextofkin) {
            return response()->json(["message"=>"This record doesn't exist", "status"=>"error"], 400);
        }
        return response()->json([
            "message"=>"Next of Kin Fetched Successfully",
            "status" => "success",
            'nextofkin' => $nextofkin,
        ], 200);
    }

    public function update(UpdateNextOfKinRequest $request, NextOfKin $nextofkin)
    {
        $nextofkin->update([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'othername' => $request->othername,
            'phone' => $request->phonenumber,
            'address' => $request->address,
            'relationship' => $request->relationship,
        ]);
        $details=json_encode($nextofkin);
        $this->AddLog($details, 'nextofkin', 'Updated');
        return response()->json([
            "message"=>"Next of Kin Added Successfully",
            "status" => "success",
            'nextofkin' => $nextofkin,
        ], 200);
    }

    public function destroy(NextOfKin $nextofkin)
    {
        $details=json_encode($nextofkin);
        $this->AddLog($details, 'nextofkin', 'Deleted');
        $nextofkin->delete();
        $response=[
            "message" => "Next of Kin Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

}

