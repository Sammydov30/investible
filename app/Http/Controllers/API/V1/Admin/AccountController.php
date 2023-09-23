<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\CreateRequest;
use App\Http\Requests\Account\UpdateRequest;
use App\Http\Requests\FetchAccountRequest;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $result = DB::table('accounts');

        if (request()->input("investor") != null) {
            $search=request()->input("investor");
            $result->where('investor', $search);
        }
        if (request()->input("accountnumber") != null) {
            $search=request()->input("accountnumber");
            $result->where('accountnumber', "like", "%{$search}%");
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

    public function getAccountName(FetchAccountRequest $request)
    {
        $accountnumber= $request->accountnumber;
        $bankcode= $request->bank;
        // $acctrequest = Http::withHeaders([
        //     "content-type" => "application/json",
        //     "Authorization" => "Bearer ".env('PAYSTACK_KEY'),
        // ])->get('https://api.paystack.co/bank/resolve?account_number='.$accountnumber.'&bank_code='.$bankcode);
        $acctrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('FW_KEY'),
        ])->post('https://api.flutterwave.com/v3/accounts/resolve', [
            "account_number"=> $request->accountnumber,
            "account_bank"=> $request->bank,
        ]);
        $res=$acctrequest->json();
        //print_r($res);
        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while fetching account", "status" => "error"], 400);
        }else{
            $response=[
                "message" => "Account Fetched Successfully",
                'account' => $res,
                "status" => "success"
            ];
            return response()->json($response, 200);
        }
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
    public function store(CreateRequest $request)
    {
        $accountt=Account::where('accountnumber', $request->accountnumber)->first();
        if ($accountt) {
            return response()->json(["message" => "Account Already Exist", "status" => "error"], 400);
        }
        $acctrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('FW_KEY'),
        ])->post('https://api.flutterwave.com/v3/accounts/resolve', [
            "account_number"=> $request->accountnumber,
            "account_bank"=> $request->bank,
        ]);
        $res=$acctrequest->json();
        //print_r($res); exit();

        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while creating account", "status" => "error"], 400);
        }else{
            $details=$res['data'];
            $account = Account::create([
                'accountnumber' => $request->accountnumber,
                'bankcode' => $request->bank,
                'accountname'=> $details['account_name'],
                'investor' => $request->investor,
                'status'=> '1'
            ]);
            $response=[
                "message" => "Account Created Successfully",
                'account' => $account,
                "status" => "success"
            ];
            return response()->json($response, 201);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $account=Account::find($id);
        if (!$account) {
            return response()->json(["message" => "Account Not Found.", "status" => "error"], 400);
        }
        $response=[
            "message" => "Account found",
            'account' => $account,
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
    public function update(UpdateRequest $request, Account $account)
    {
        $accountt=Account::where('accountnumber', $request->accountnumber)->where('id', '!=', $account->id)->first();

        if ($accountt) {
            return response()->json(["message" => "Account Already Exist", "status" => "error"], 400);
        }
        $acctrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('FW_KEY'),
        ])->post('https://api.flutterwave.com/v3/accounts/resolve', [
            "account_number"=> $request->accountnumber,
            "account_bank"=> $request->bank,
        ]);
        $res=$acctrequest->json();

        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while creating account", "status" => "error"], 400);
        }else{
            $details=$res['data'];
            $account->update([
                'accountnumber' => $request->accountnumber,
                'bankcode' => $request->bank,
                'accountname'=> $details['account_name'],
                'status'=> '1'
            ]);
            $response=[
                "message" => "Account Created Successfully",
                'account' => $account,
                "status" => "success"
            ];
            return response()->json($response, 201);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Account $account)
    {
        $account->delete();
        $response=[
            "message" => "Account Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }
}
