<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\CreateInvestmentRequest;
use App\Http\Requests\Investment\UpdateInvestmentRequest;
use App\Http\Requests\Investment\UploadOldInvestmentRequest;
use App\Models\Account;
use App\Models\BulkPaymentHistory;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\PaymentHistory;
use App\Models\Plan;
use App\Traits\ActionLogTrait;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class InvestmentController extends Controller
{
    use ActionLogTrait;
    public function index(Request $request)
    {
        $result = Investment::with('investmentOwner', 'nok', 'bank');
        if (request()->input("search") != null) {
            $search=request()->input("search");
            $result->whereHas('investmentOwner', function ($query) use($search)
            {
                $query->where('firstname', "like", "%{$search}%")
                ->orWhere('lastname', "like", "%{$search}%")
                ->orWhere('othername', "like", "%{$search}%");
            });
        }
        if (request()->input("investmentid")!=null) {
            $result->where('investmentid', request()->input("investmentid"));
        }
        if (request()->input("investor")!=null) {
            $result->where('investor', request()->input("investor"));
        }
        if (request()->input("type")!=null) {
            $result->where('type', request()->input("type"));
        }
        if (request()->input("status")!=null) {
            $result->where('status', request()->input("status"));
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

        $investments=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($investments, 200);
    }

    public function show($investment)
    {
        $investment=Investment::find($investment);
        if (!$investment) {
            return response()->json(["message"=>"This record doesn't exist", "status"=>"error"], 400);
        }
        return response()->json([
            "message"=>"Investment Fetched Successfully",
            "status" => "success",
            'investment' => $investment,
        ], 200);
    }

    public function store(CreateInvestmentRequest $request)
    {

        $accountinfo=Account::where('id', $request->account)->first();
        $accountnumber=$accountinfo->accountnumber;
        $bankcode=$accountinfo->bankcode;
        $planinfo=Plan::where('id', $request->plan)->first();
        $type=($planinfo->duration=='1')?'1':'2';
        $amountpaid=$planinfo->amount;
        $amounttobereturned=(($planinfo->percentage/100)*$amountpaid)+$amountpaid;
        $percentage=$planinfo->percentage;
        $return=$planinfo->returns*$request->howmany;
        $amountpaidsofar='0';
        $timeduration=$planinfo->no_of;
        $timeremaining=$timeduration;
        $startdate=$this->GetStartDate($request->agreementdate, $type);
        $stopdate=$this->GetStopDate($startdate, $timeduration, $type);
        $investment=Investment::create([
            'investmentid' => $this->getInvestmentNO(),
            'investor'=>$request->investor,
            'account' => $request->account,
            'accountnumber'=> $accountnumber,
            'bankcode'=> $bankcode,
            'nextofkin' => $request->nextofkin,
            'planid' => $request->plan,
            'type' => $type,
            'agreementdate' => $request->agreementdate,
            'amountpaid'=>intval($amountpaid),
            'amount_to_be_returned'=>$amounttobereturned,
            'percentage'=>$percentage,
            'return'=>$return,
            'amountpaidsofar'=>$amountpaidsofar,
            'timeduration'=>$timeduration,
            'timeremaining'=>$timeremaining,
            'startdate'=>$startdate,
            'stopdate'=>$stopdate,
            'period'=>$type,
            'witnessname' => $request->witnessname,
            'witnessaddress' => $request->witnessaddress,
            'witnessphone' => $request->witnessphone,
            'status' => '0',
        ]);
        $this->AddLog(json_encode($investment), 'investment', 'Created');
        return response()->json([
            "message"=>"Investment Created Successfully",
            "status" => "success",
            'investment' => $investment,
        ], 200);
    }

    public function uploadold(UploadOldInvestmentRequest $request)
    {
        $accountinfo=Account::where('id', $request->account)->first();
        $accountnumber=$accountinfo->accountnumber;
        $bankcode=$accountinfo->bankcode;
        $investment=Investment::create([
            'investmentid' => $this->getInvestmentNO(),
            'investor'=>$request->investor,
            'account' => $request->account,
            'accountnumber'=> $accountnumber,
            'bankcode'=> $bankcode,
            'nextofkin' => $request->nextofkin,
            'planid' => $request->plan,
            'type' => $request->duration,
            'agreementdate' => $request->agreementdate,
            'amountpaid'=>$request->amountpaid,
            'amount_to_be_returned'=>$request->amounttobereturned,
            'percentage'=>$request->percentage,
            'return'=>$request->return,
            'amountpaidsofar'=>$request->amountpaidsofar,
            'timeduration'=>$request->no_of,
            'timeremaining'=>$request->timeremaining,
            'startdate'=>$request->startdate,
            'stopdate'=>$request->stopdate,
            'period'=>$request->duration,
            'witnessname' => $request->witnessname,
            'witnessaddress' => $request->witnessaddress,
            'witnessphone' => $request->witnessphone,
            'status' => $request->status,
        ]);
        $this->AddLog(json_encode($investment), 'investment', 'Upload');
        return response()->json([
            "message"=>"Investment Created Successfully",
            "status" => "success",
            'investor' => $investment,
        ], 200);
    }

    public function update(UpdateInvestmentRequest $request, Investment $investment)
    {
        $accountinfo=Account::where('id', $request->account)->first();
        $accountnumber=$accountinfo->accountnumber;
        $bankcode=$accountinfo->bankcode;
        $investment->update([
            'account' => $request->account,
            'accountnumber'=> $accountnumber,
            'bankcode'=> $bankcode,
            'nextofkin' => $request->nextofkin,
            'planid' => $request->plan,
            'type' => $request->duration,
            'agreementdate' => $request->agreementdate,
            'amountpaid'=>$request->amountpaid,
            'amount_to_be_returned'=>$request->amounttobereturned,
            'percentage'=>$request->percentage,
            'return'=>$request->return,
            'amountpaidsofar'=>$request->amountpaidsofar,
            'timeduration'=>$request->no_of,
            'timeremaining'=>$request->timeremaining,
            'startdate'=>$request->startdate,
            'stopdate'=>$request->stopdate,
            'period'=>$request->duration,
            'witnessname' => $request->witnessname,
            'witnessaddress' => $request->witnessaddress,
            'witnessphone' => $request->witnessphone,
            'status' => $request->status
        ]);
        $this->AddLog(json_encode($investment), 'investment', 'Updated');
        return response()->json([
            "message"=>"Investment Updated Successfully",
            "status" => "success",
            'investment' => $investment,
        ], 200);
    }

    public function updateReady()
    {
        $date=date('Y-m-d');
        $investments=Investment::where('status', '0')->get();
        foreach ($investments as $investment) {
            $invdate=date('Y-m-d', strtotime($investment->startdate));
            if ($invdate<=$date) {
                Investment::where('id', $investment->id)->update([
                    'status' => '1',
                ]);
            }
        }
        Investment::where('timeremaining', '0')->update([
            'status' => '2',
        ]);
        $this->AddLog('Got investment ready for '.$date, 'investment', 'GotReady');
        return response()->json([
            "message"=>"Investments are ready for period payment Successfully",
            "status" => "success",
        ], 200);
    }

    public function payInvestment(Request $request)
    {
        if (empty($request->investmentid)) {
            return response()->json(["message"=>"Investment Id is required", "status"=>"error"], 400);
        }
        $investment=Investment::where('investmentid', $request->investmentid)->first();
        if (!$investment) {
            return response()->json(["message"=>"This record doesn't exist", "status"=>"error"], 400);
        }
        if ($investment->status=='0') {
            return response()->json(["message"=>"This Investment is not due for payment yet", "status"=>"error"], 400);
        }
        if ($investment->status=='2') {
            return response()->json(["message"=>"This Investment's payment is over", "status"=>"error"], 400);
        }
        $refcode="IP".time();
        $date=date("d-m-Y");
        $paymentrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('FW_KEY'),
        ])->post('https://api.flutterwave.com/v3/transfers', [
            "account_number"=> $investment->accountnumber,
            "account_bank"=> $investment->bankcode,
            "amount"=> intval($investment->return),
            "narration"=> "Gavice Investment Payment for ".$date,
            "currency"=> "NGN",
            "reference"=> $refcode,
            //"callback_url"=> "https://www.flutterwave.com/ng/",
            "debit_currency"=> "NGN"
        ]);
        $res=$paymentrequest->json();
        //print_r($res); exit();
        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while fetching account", "status" => "error"], 400);
        }
        if ($res['status']=='error') {
            return response()->json(["message" => "An Error occurred while fetching account", "status" => "error"], 400);
        }
        $payment=PaymentHistory::create([
            'transfercode'=>$refcode,
            'investmentid'=>$investment->investmentid,
            'investorid'=>$investment->investor,
            'accountnumber'=>$investment->accountnumber,
            'bankcode'=>$investment->bankcode,
            'amount'=>$investment->return,
            'pdate'=>$date,
            'narration'=>"Gavice Investment Payment for ".$date,
            'status'=>'0'
        ]);
        $this->AddLog(json_encode($payment), 'paymenthistory', 'Created');
        //$newapsf=$investment->amountpaidsofar+$investment->return;
        //$newtr=$investment->timeremaining-1;
        Investment::where('investmentid', $investment->investmentid)->update([
            //'amountpaidsofar'=>$newapsf,
            //'timeremaining'=>$newtr,
            'lastpaymentdate'=>$date
        ]);
        $this->AddLog(json_encode($investment), 'investment', 'PayUpdate');
        return response()->json([
            "message"=>"Investment Payed Successfully",
            "status" => "success",
            'payment' => $payment,
        ], 200);
    }
    public function paybulkWeeklyInvestment(Request $request)
    {
        $investments=Investment::where('type', '1')->where('status', '1')->get();
        $k=1;
        $refcode="IP".time();
        $date=date("d-m-Y");
        /////////////////
        ///Get Bulk data
        ///////////////////
        $bulkdata=[];
        foreach ($investments as $investment) {
            $newdata=(object)[
                "bank_code"=> $investment->bankcode,
                "account_number"=> $investment->accountnumber,
                "amount"=> intval($investment->return),
                "narration"=> "Investment Payment for ".$date,
                "currency"=> "NGN",
                "reference"=> $refcode.$k
            ];
            array_push($bulkdata, $newdata);
            $k++;
        }
        //print_r($bulkdata);

        /////////////////
        ///Make Payment
        ///////////////////
        $paymentrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('FW_KEY'),
        ])->post('https://api.flutterwave.com/v3/bulk-transfers', [
            "title"=> "Weekly Bulk Payment for ".$date,
            "bulk_data"=> $bulkdata,
        ]);
        $res=$paymentrequest->json();
        //print_r($res); exit();
        if (!$res['status']) {
            $this->AddLog(json_encode($bulkdata), 'weekbulkpayment', 'FailedPayment');
            return response()->json(["message" => "An Error occurred while executing action", "status" => "error"], 400);
        }
        if ($res['status']=='error') {
            $this->AddLog(json_encode($bulkdata), 'weekbulkpayment', 'FailedPayment');
            return response()->json(["message" => "An Error occurred while executing action", "status" => "error"], 400);
        }
        $transferid=$res['data']['id'];
        BulkPaymentHistory::created([
            'transferid'=>$transferid,
        ]);
        /////////////////
        ///Update records
        ///////////////////
        foreach ($investments as $investment) {
            PaymentHistory::create([
                'transfercode'=>$refcode.$k,
                'investmentid'=>$investment->investmentid,
                'investorid'=>$investment->investor,
                'accountnumber'=>$investment->accountnumber,
                'bankcode'=>$investment->bankcode,
                'amount'=>$investment->return,
                'pdate'=>$date,
                'narration'=>"Gavice Weekly Investment Payment for ".$date,
                'status'=>'0'
            ]);
            //$newapsf=$investment->amountpaidsofar+$investment->return;
            //$newtr=$investment->timeremaining-1;
            Investment::where('investmentid', $investment->investmentid)->update([
                //'amountpaidsofar'=>$newapsf,
                //'timeremaining'=>$newtr,
                'lastpaymentdate'=>$date
            ]);
            $k++;
        }
        $this->AddLog(json_encode($bulkdata), 'weekbulkpayment', 'SuccessPayment');
        return response()->json([
            "message"=>"Investment Payment Dispatched Successfully",
            "status" => "success",
        ], 200);
    }

    public function paybulkMonthlyInvestment(Request $request)
    {
        $investments=Investment::where('type', '2')->where('status', '1')->get();
        $k=1;
        $refcode="IP".time();
        $date=date("d-m-Y");
        $mdate=date("F-Y");
        /////////////////
        ///Get Bulk data
        ///////////////////
        $bulkdata=[];
        foreach ($investments as $investment) {
            $newdata=(object)[
                "bank_code"=> $investment->bankcode,
                "account_number"=> $investment->accountnumber,
                "amount"=> intval($investment->return),
                "narration"=> "Investment Payment for ".$mdate,
                "currency"=> "NGN",
                "reference"=> $refcode.$k
            ];
            array_push($bulkdata, $newdata);
            $k++;
        }
        //print_r($bulkdata);

        /////////////////
        ///Make Payment
        ///////////////////
        $paymentrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('FW_KEY'),
        ])->post('https://api.flutterwave.com/v3/bulk-transfers', [
            "title"=> "Monthly Bulk Payment for ".$mdate,
            "bulk_data"=> $bulkdata,
        ]);
        $res=$paymentrequest->json();
        //print_r($res); exit();
        if (!$res['status']) {
            $this->AddLog(json_encode($bulkdata), 'monthbulkpayment', 'FailedPayment');
            return response()->json(["message" => "An Error occurred while executing action", "status" => "error"], 400);
        }
        if ($res['status']=='error') {
            $this->AddLog(json_encode($bulkdata), 'monthbulkpayment', 'FailedPayment');
            return response()->json(["message" => "An Error occurred while executing action", "status" => "error"], 400);
        }
        $transferid=$res['data']['id'];
        BulkPaymentHistory::created([
            'transferid'=>$transferid,
        ]);
        /////////////////
        ///Update records
        ///////////////////
        foreach ($investments as $investment) {
            PaymentHistory::create([
                'transfercode'=>$refcode.$k,
                'investmentid'=>$investment->investmentid,
                'investorid'=>$investment->investor,
                'accountnumber'=>$investment->accountnumber,
                'bankcode'=>$investment->bankcode,
                'amount'=>$investment->return,
                'pdate'=>$date,
                'narration'=>"Investment Payment for ".$mdate,
                'status'=>'0'
            ]);
            $newapsf=$investment->amountpaidsofar+$investment->return;
            $newtr=$investment->timeremaining-1;
            Investment::where('investmentid', $investment->investmentid)->update([
                'amountpaidsofar'=>$newapsf,
                'timeremaining'=>$newtr,
                'lastpaymentdate'=>$date
            ]);
            $k++;
        }
        $this->AddLog(json_encode($bulkdata), 'monthbulkpayment', 'SuccessPayment');
        return response()->json([
            "message"=>"Investment Payment Dispatched Successfully",
            "status" => "success",
        ], 200);
    }

    public function destroy(Investment $investment)
    {
        $this->AddLog(json_encode($investment), 'investment', 'Deleted');
        $investment->delete();
        $response=[
            "message" => "Investment Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function getInvestmentNO() {
        $i=0;
        while ( $i==0) {
            $investmentno=rand(10000000, 99999999);
            $check=Investment::where('investmentid', $investmentno)->first();
            if (!$check) {
                $i=1;
            }
        }
        return "GIV".$investmentno;
    }
    public function GetStartDate($adate, $duration){
        $date = new DateTime($adate);
        if ($duration=='1') {
            $date->modify('next monday');
            $nextmonday = $date->format('d-m-Y');
            $date = new DateTime($nextmonday);
            $date->modify('+ 14 days');
        } else {
            $date->modify("first day of next month");
            $month = $date->format('m');
            $nextmonth = $date->format('d-m-Y');
            $date = new DateTime($nextmonth);
            if ($month=='02') {
                $date->modify('+ 27 days');
            } else {
                $date->modify('+ 29 days');
            }
        }

        return $date->format('d-m-Y');
    }
    public function GetStopDate($sdate, $no_of, $duration){
        $date = new DateTime($sdate);
        if ($duration=='1') {
            $date->modify("+ $no_of weeks");
        } else {
            $date->modify("+ $no_of months");
        }
        return $date->format('d-m-Y');
    }

    public function GetPayingAmount(Request $request)
    {
        $investments=Investment::where('type', '1')->where('status', '1')->get();
        $totalamount=0;
        foreach ($investments as $investment) {
            $totalamount=$totalamount+intval($investment->return);
        }
        return response()->json([
            "message"=>"Week Investment Payout Amount Generated Successfully",
            "status" => "success",
            'amount' => $totalamount,
        ], 200);
    }

    public function payweekRemaining(Request $request)
    {
        $investments=Investment::where('type', '1')->where('status', '1')->whereNull('lastpaymentdate')->get();
        $k=1;
        $refcode="IP".time();
        $date=date("d-m-Y");
        /////////////////
        ///Get Bulk data
        ///////////////////
        $bulkdata=[];
        foreach ($investments as $investment) {
            $newdata=(object)[
                "bank_code"=> $investment->bankcode,
                "account_number"=> $investment->accountnumber,
                "amount"=> intval($investment->return),
                "narration"=> "Investment Payment for ".$date,
                "currency"=> "NGN",
                "reference"=> $refcode.$k
            ];
            array_push($bulkdata, $newdata);
            $k++;
        }
        //print_r($bulkdata); exit();

        /////////////////
        ///Make Payment
        ///////////////////
        $paymentrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('FW_KEY'),
        ])->post('https://api.flutterwave.com/v3/bulk-transfers', [
            "title"=> "Weekly Bulk Payment for ".$date,
            "bulk_data"=> $bulkdata,
        ]);
        $res=$paymentrequest->json();
        //print_r($res); exit();
        if (!$res['status']) {
            $this->AddLog(json_encode($bulkdata), 'weekbulkpayment', 'FailedPayment');
            return response()->json(["message" => "An Error occurred while executing action", "status" => "error"], 400);
        }
        if ($res['status']=='error') {
            $this->AddLog(json_encode($bulkdata), 'weekbulkpayment', 'FailedPayment');
            return response()->json(["message" => "An Error occurred while executing action", "status" => "error"], 400);
        }
        $transferid=$res['data']['id'];
        BulkPaymentHistory::created([
            'transferid'=>$transferid,
        ]);
        /////////////////
        ///Update records
        ///////////////////
        foreach ($investments as $investment) {
            PaymentHistory::create([
                'transfercode'=>$refcode.$k,
                'investmentid'=>$investment->investmentid,
                'investorid'=>$investment->investor,
                'accountnumber'=>$investment->accountnumber,
                'bankcode'=>$investment->bankcode,
                'amount'=>$investment->return,
                'pdate'=>$date,
                'narration'=>"Gavice Weekly Investment Payment for ".$date,
                'status'=>'0'
            ]);
            //$newapsf=$investment->amountpaidsofar+$investment->return;
            //$newtr=$investment->timeremaining-1;
            Investment::where('investmentid', $investment->investmentid)->update([
                //'amountpaidsofar'=>$newapsf,
                //'timeremaining'=>$newtr,
                'lastpaymentdate'=>$date
            ]);
            $k++;
        }
        $this->AddLog(json_encode($bulkdata), 'weekbulkpayment', 'SuccessPayment');
        return response()->json([
            "message"=>"Investment Payment Dispatched Successfully",
            "status" => "success",
        ], 200);
    }

    public function GetRemainingAmount(Request $request)
    {
        $date=$request->date;
        $investments=Investment::where('type', '1')->where('status', '1')
        ->where(function($query) use($date){
            $query->whereNull('lastpaymentdate')->orWhere('lastpaymentdate', '!=', $date);
        })->get();
        $totalamount=0;
        foreach ($investments as $investment) {
            $totalamount=$totalamount+intval($investment->return);
        }
        return response()->json([
            "message"=>"Week Remaining Payout Amount Generated Successfully",
            "status" => "success",
            'amount' => $totalamount,
        ], 200);
    }

}

