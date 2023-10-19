<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Plan\CreatePlanRequest;
use App\Http\Requests\Plan\UpdatePlanRequest;
use App\Models\Plan;
use App\Traits\ActionLogTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    use ActionLogTrait;
    public function index()
    {
        $result = DB::table('plans');
        if (request()->input("name") != null) {
            $search=request()->input("name");
            $result->where('name', "like", "%{$search}%");
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

        $plan=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($plan, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreatePlanRequest $request)
    {

        $plan = Plan::create([
            'name' => $request->name,
            'amount' => $request->amount,
            'percentage' => $request->percentage,
            'returns' => $request->returns,
            'duration' => $request->duration,
            'no_of' => $request->no_of,
            'category' => $request->category,
            'description' => $request->description,
        ]);
        $this->AddLog(json_encode($plan), 'plan', 'Created');

        $response=[
            "message" => "Plan Created Successfully",
            'plan' => $plan,
            "status" => "success"
        ];

        return response()->json($response, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $plan=Plan::find($id);
        if (!$plan) {
            return response()->json(["message" => " Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Plan found",
            'plan' => $plan,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        $plan->update([
            'name' => $request->name,
            'amount' => $request->amount,
            'percentage' => $request->percentage,
            'returns' => $request->returns,
            'duration' => $request->duration,
            'no_of' => $request->no_of,
            'category' => $request->category,
            'description' => $request->description,
        ]);
        $this->AddLog(json_encode($plan), 'plan', 'Updated');
        $response=[
            "message" => "Plan Updated Successfully",
            'plan' => $plan,
            "status" => "success"
        ];

        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Plan $plan)
    {
        $this->AddLog(json_encode($plan), 'plan', 'Deleted');
        $plan->delete();
        $response=[
            "message" => "Plan Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
