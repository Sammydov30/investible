<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investment\CreateInvestmentRequest;
use App\Http\Requests\Investment\SharpUpdateInvestmentMRequest;
use App\Http\Requests\Investment\SharpUpdateInvestmentRequest;
use App\Http\Requests\Investment\UpdateInvestmentRequest;
use App\Http\Requests\Investment\UploadOldInvestmentRequest;
use App\Jobs\Admin\AdminPhoneOtpJob;
use App\Jobs\Admin\EmailOtpJob;
use App\Models\Account;
use App\Models\Admin;
use App\Models\Bank;
use App\Models\BulkPaymentHistory;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\PaymentHistory;
use App\Models\Plan;
use App\Traits\ActionLogTrait;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use function PHPUnit\Framework\isNull;

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
            })
            ->orWhere('accountnumber', $search)
            ->orWhere('investmentid', $search);
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
        if (request()->input("freeze")!=null) {
            $result->where('hold', request()->input("freeze"));
        }
        if (request()->input("status")!=null) {
            $result->where('status', request()->input("status"));
            // if (request()->input("status")=='1') {
            //     $result->whereIn('status', ['0', '1']);
            // }
        }
        if (request()->input("stopdate") != null) {
            if (request()->input("startdate") == null) {
                return response()->json(["message" => "start-date required if end-date given.", "status" => "error"], 400);
            }
            $startdate= date('d-m-Y',strtotime(request()->input("startdate")));
            $enddate=date('d-m-Y',strtotime(request()->input("stopdate")));
            $result->where(function($query) use($startdate, $enddate) {
                $query->where('startdate', '>=', $startdate)->where('startdate', '<=', $enddate);
            });
        }elseif(request()->input("startdate") != null){
            $startdate= date('d-m-Y',strtotime(request()->input("startdate")));
            $enddate=date("d-m-Y");
            $result->where(function($query) use($startdate, $enddate) {
                $query->where('startdate', '>=', $startdate)->where('startdate', '<=', $enddate);
            });
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

    public function exportable(Request $request)
    {
        $result = Investment::with('investmentOwner')->whereIn('status', ['0', '1']);
        if (request()->input("type")!=null) {
            $result->where('type', request()->input("type"));
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
            $perPage=5000;
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
        $amountpaid=$planinfo->amount*$request->howmany;
        $amounttobereturned=(($planinfo->percentage/100)*$amountpaid)+$amountpaid;
        $percentage=$planinfo->percentage;
        $return=$planinfo->returns*$request->howmany;
        $amountpaidsofar='0';
        $timeduration=$planinfo->no_of;
        $timeremaining=$timeduration;
        $startdate=$this->GetStartDate($request->agreementdate, $type);
        $stopdate=$this->GetStopDate($startdate, $timeduration, $type);

        if ($request->file('pop')) {
            $file =$request->file('pop');
            $extension = $file->getClientOriginalExtension();
            $filename = time().'.' . $extension;
            $file->move(public_path('uploads/pop/'), $filename);
            $pop= 'uploads/pop/'.$filename;
        }else{
            $pop=null;
        }

        if ($request->file('agreementdoc')) {
            $file =$request->file('agreementdoc');
            $extension = $file->getClientOriginalExtension();
            $filename = time().'.' . $extension;
            $file->move(public_path('uploads/agreementdoc/'), $filename);
            $agreementdoc= 'uploads/agreementdoc/'.$filename;
        }else{
            $agreementdoc=null;
        }

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
            'pop'=>$pop,
            'agreementdoc'=>$agreementdoc,
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

        if ($request->file('pop')) {
            $file =$request->file('pop');
            $extension = $file->getClientOriginalExtension();
            $filename = time().'.' . $extension;
            $file->move(public_path('uploads/pop/'), $filename);
            $pop= 'uploads/pop/'.$filename;
        }else{
            $pop=null;
        }

        if ($request->file('agreementdoc')) {
            $file =$request->file('agreementdoc');
            $extension = $file->getClientOriginalExtension();
            $filename = time().'.' . $extension;
            $file->move(public_path('uploads/agreementdoc/'), $filename);
            $agreementdoc= 'uploads/agreementdoc/'.$filename;
        }else{
            $agreementdoc=null;
        }

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
            'pop'=>$pop,
            'agreementdoc'=>$agreementdoc,
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

        if ($request->file('pop')) {
            $file =$request->file('pop');
            $extension = $file->getClientOriginalExtension();
            $filename = time().'.' . $extension;
            $file->move(public_path('uploads/pop/'), $filename);
            $pop= 'uploads/pop/'.$filename;
        }else{
            $pop=null;
        }

        if ($request->file('agreementdoc')) {
            $file =$request->file('agreementdoc');
            $extension = $file->getClientOriginalExtension();
            $filename = time().'.' . $extension;
            $file->move(public_path('uploads/agreementdoc/'), $filename);
            $agreementdoc= 'uploads/agreementdoc/'.$filename;
        }else{
            $agreementdoc=null;
        }

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
            'status' => $request->status,
        ]);
        if ($request->file('pop')) {
            $investment->update([
                'pop'=>$pop,
            ]);
        }
        if ($request->file('agreementdoc')) {
            $investment->update([
                'agreementdoc'=>$agreementdoc,
            ]);
        }
        $this->AddLog(json_encode($investment), 'investment', 'Updated');
        return response()->json([
            "message"=>"Investment Updated Successfully",
            "status" => "success",
            'investment' => $investment,
        ], 200);
    }

    public function sharpupdate(SharpUpdateInvestmentRequest $request, Investment $investment)
    {
        if (empty($request->stopdate)) {
            $date2 = new DateTime($request->startdate);
            $no_of=$request->no_of-1;
            $date2->modify("+ $no_of weeks");
            $stopdate = $date2->format('d-m-Y');
        }else{
            $stopdate = $request->stopdate;
        }
        $currdate=new DateTime();
        $eDate = new DateTime($stopdate);
        $amount=$investment->return;
        $startDate = new DateTime($request->startdate);
        $endDate = new DateTime($stopdate);
        $difference = $endDate->diff($startDate);
        $totalweeks=($difference->format("%a"))/7;
        //echo $totalweeks; exit();
        if ($currdate<$eDate) {
            // if ($totalweeks>=$request->no_of) {
            //     $status="2";
            // }else{
            //     $status="1";
            // }
            $status="1";
        }else{
            $status="2";
        }

        $currdate=date('d-m-Y');
        if (date('l', strtotime($currdate))=='Monday') {
            $lastmonday=$currdate;
        } else {
            $date = new DateTime();
            $date->modify('last monday');
            $lastmonday = $date->format('d-m-Y');
        }

        $startDate = new DateTime($request->startdate);
        $endDate = new DateTime($lastmonday);

        $difference = $endDate->diff($startDate);
        $totalweekspaid=(($difference->format("%a"))/7)+1;
        //Time remaining
        $timeremaining=$request->no_of-$totalweekspaid;
        //Amount Paid so far
        $amountpaidsofar=$amount*$totalweekspaid;
        //agreement date
        $date = new DateTime($request->startdate);
        $date->modify('- 15 days');
        $agreementdate=$date->format('d-m-Y');
        $investment->update([
            'agreementdate' => $agreementdate,
            'amountpaidsofar'=>$amountpaidsofar,
            'timeduration'=>$request->no_of,
            'timeremaining'=>$timeremaining,
            'startdate'=>$request->startdate,
            'stopdate'=>$stopdate,
            'status' => $status
        ]);
        $this->AddLog(json_encode($investment), 'investment', 'Sharp Updated');
        return response()->json([
            "message"=>"Investment Updated Successfully",
            "status" => "success",
            'investment' => $investment,
        ], 200);
    }

    public function sharpupdateM(SharpUpdateInvestmentMRequest $request, Investment $investment)
    {
        if (empty($request->stopdate)) {
            $date2 = new DateTime($request->startdate);
            $no_of=$request->no_of-1;
            $date2->modify("+ $no_of months");
            $stopdate = $date2->format('d-m-Y');
        }else{
            $stopdate = $request->stopdate;
            $stopdate = date("d-m-Y", strtotime($stopdate));
        }
        $currdate=new DateTime();
        $eDate = new DateTime($stopdate);
        $amount=$investment->return;
        $startDate = new DateTime($request->startdate);
        $endDate = new DateTime($stopdate);
        $difference = $endDate->diff($startDate);
        $totalweeks=($difference->format("%a"))/7;
        //echo $totalweeks; exit();
        if ($currdate<$eDate) {
            // if ($totalweeks>=$request->no_of) {
            //     $status="2";
            // }else{
            //     $status="1";
            // }
            $status="1";
        }else{
            $status="2";
        }

        $currdate=date('d-m-Y');

        $lastmonth=date('d-m-Y');

        $startdate = $request->startdate;
        $startdate = date("d-m-Y", strtotime($startdate));
        $startDate = new DateTime($startdate);
        $endDate = new DateTime($lastmonth);

        $difference = $endDate->diff($startDate);
        $interval = $startDate->diff($endDate);
        // $totalmonthspaid=(($difference->format("%a"))/30)+1;
        $totalmonthspaid=$interval->m;
        //Time remaining
        $timeremaining=$request->no_of-$totalmonthspaid;
        //Amount Paid so far
        $amountpaidsofar=$amount*$totalmonthspaid;
        //agreement date
        $date = new DateTime($request->startdate);
        $date->modify('- 1 months');
        $agreementdate=$date->format('d-m-Y');
        $investment->update([
            'amountpaid'=>$request->amountpaid,
            'amount_to_be_returned'=>$amount*$request->no_of,
            'agreementdate' => $agreementdate,
            'amountpaidsofar'=>$amountpaidsofar,
            'timeduration'=>$request->no_of,
            'timeremaining'=>$timeremaining,
            'startdate'=>$request->startdate,
            'stopdate'=>$stopdate,
            'status' => $status
        ]);
        $this->AddLog(json_encode($investment), 'investment', 'Sharp Updated');
        return response()->json([
            "message"=>"Investment Updated Successfully",
            "status" => "success",
            'investment' => $investment,
        ], 200);
    }

    public function sharpupdateUM(SharpUpdateInvestmentMRequest $request)
    {

        $date2 = new DateTime($request->startdate);
        $no_of=$request->no_of-1;
        $date2->modify("+ $no_of months");
        $stopdate = $date2->format('d-m-Y');

        $investment=Investment::where('investmentid', $request->investmentid);
        $amount=$investment->first()->return;

        $startdate = $request->startdate;
        $investment->update([
            'amountpaid'=>$request->amountpaid,
            'amount_to_be_returned'=>$amount*$request->no_of,
            'timeduration'=>$request->no_of,
            'startdate'=>$startdate,
            'stopdate'=>$stopdate,
        ]);
        $investment=Investment::where('investmentid', $request->investmentid)->first();
        return response()->json([
            "message"=>"Investment Updated Successfully",
            "status" => "success",
            'investment' => $investment,
        ], 200);
    }

    public function updateReady()
    {
        $currdate=date('Y-m-d');
        if (date('l', strtotime($currdate))=='Monday') {
            $date=$currdate;
        } else {
            $date = new DateTime($currdate);
            $date->modify('next monday');
            $date=$date->format('Y-m-d');
        }
        $ddd=$date;
        //echo $date; exit();
        $investments=Investment::where('type', '1')->where('status', '0')->get();
        foreach ($investments as $investment) {
            $invdate=date('Y-m-d', strtotime($investment->startdate));
            if ($invdate<=$date) {
                Investment::where('id', $investment->id)->update([
                    'status' => '1',
                ]);
            }
        }

        Investment::where('type', '1')->where('timeremaining', '<=', '0')->update([
            'status' => '2',
        ]);
        $this->AddLog('Got investment ready for '.$ddd, 'investment', 'GotReady');
        return response()->json([
            "message"=>"Investments are ready for period payment Successfully",
            "status" => "success",
        ], 200);
    }

    public function updateReadyMonth()
    {
        $currdate=date('Y-m-d');
        $date=date('m-Y');
        $currmonth = date('m');
        $curryear=date('Y');
        $ddd=date('F-Y');
        //echo $date; exit();
        $investments=Investment::where('type', '2')->where('status', '0')->get();
        foreach ($investments as $investment) {
            $invdate=date('m-Y', strtotime($investment->startdate));
            $invmonth=date('m', strtotime($investment->startdate));
            $invyear=date('Y', strtotime($investment->startdate));
            if ($invyear<$curryear) {
                Investment::where('id', $investment->id)->update([
                    'status' => '1',
                ]);
            }elseif ($invyear==$curryear) {
                if ($invmonth<=$currmonth) {
                    Investment::where('id', $investment->id)->update([
                        'status' => '1',
                    ]);
                }
            }
        }

        Investment::where('type', '2')->where('timeremaining', '<=', '0')->update([
            'status' => '2',
        ]);
        $this->AddLog('Got investment ready for '.$ddd, 'investment', 'GotReady');
        return response()->json([
            "message"=>"Investments are ready for period payment Successfully",
            "status" => "success",
        ], 200);
    }

    public function updatePast()
    {
        //close investments
        $investments=Investment::where('status', '1')->where('type', '1')->get();
        foreach ($investments as $investment) {
            //$invdate=date('Y-m-d', strtotime($investment->startdate));
            //$stopdate = $investment->stopdate;
            $date2 = new DateTime($investment->startdate);
            $no_of=$investment->timeduration-1;
            $date2->modify("+ $no_of weeks");
            $stopdate = $date2->format('d-m-Y');

            $currdate=new DateTime();
            $eDate = new DateTime($stopdate);
            $amount=$investment->return;
            $startDate = new DateTime($investment->startdate);
            $endDate = new DateTime($stopdate);
            $difference = $endDate->diff($startDate);
            $totalweeks=($difference->format("%a"))/7;

            if ($currdate<$eDate) {
                $status="1";
            }else{
                $status="2";
            }
            $currdate2=date('d-m-Y');
            if (date('l', strtotime($currdate2))=='Monday') {
                $lastmonday=$currdate2;
            } else {
                $date = new DateTime();
                $date->modify('last monday');
                $lastmonday = $date->format('d-m-Y');
            }

            $startDate = new DateTime($investment->startdate);
            $endDate = new DateTime($lastmonday);

            $difference = $endDate->diff($startDate);
            $totalweekspaid=(($difference->format("%a"))/7)+1;

            if ($currdate->format('d-m-Y')===$eDate->format('d-m-Y')) {
                //Don't stop yet
                $status="1";
                $timeremaining=($investment->timeduration-$totalweekspaid)+1;
                $amountpaidsofar=($amount*$totalweekspaid)-$amount;
            }else{
                //Time remaining
                $timeremaining=$investment->timeduration-$totalweekspaid;
                //Amount Paid so far
                $amountpaidsofar=$amount*$totalweekspaid;
            }
            if ($timeremaining<=0) {
                $timeremaining=0;
                $amountpaidsofar=$amount*$investment->timeduration;
            }

            //agreement date
            $date = new DateTime($investment->startdate);
            $date->modify('- 15 days');
            $agreementdate=$date->format('d-m-Y');
            Investment::where('id', $investment->id)->update([
                // 'agreementdate' => $agreementdate,
                'amountpaidsofar'=>$amountpaidsofar,
                'timeremaining'=>$timeremaining,
                'stopdate'=>$stopdate,
                'status' => $status
            ]);
        }
    }

    public function justpayInvestment(Request $request)
    {
        $admin=auth()->user();
        $currtime=time();
        if(empty($request->otp)){
            return response()->json(["message" => "OTP is Required. Try again", "status" => "error"], 400);
        }
        if($request->otp!=$admin->otp){
            return response()->json(["message" => "OTP Verification Failed. Try again", "status" => "error"], 400);
        }elseif($currtime>$admin->expiration){
            return response()->json(["message" => "OTP Expired.", "status" => "error"], 400);
        }
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
        Investment::where('investmentid', $investment->investmentid)->update([
            'lastpaymentdate'=>$date
        ]);
        $this->AddLog(json_encode($investment), 'investment', 'PayUpdate');
        return response()->json([
            "message"=>"Investment Payed Successfully",
            "status" => "success",
            'payment' => $payment,
        ], 200);
    }
    public function payInvestment(Request $request)
    {
        $admin=auth()->user();
        $currtime=time();
        // if(empty($request->otp)){
        //     return response()->json(["message" => "OTP is Required. Try again", "status" => "error"], 400);
        // }
        // if($request->otp!=$admin->otp){
        //     return response()->json(["message" => "OTP Verification Failed. Try again", "status" => "error"], 400);
        // }elseif($currtime>$admin->expiration){
        //     return response()->json(["message" => "OTP Expired.", "status" => "error"], 400);
        // }

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
        // if ($investment->hold=='1') {
        //     return response()->json(["message"=>"This Investment is frozen at the moment", "status"=>"error"], 400);
        // }
        if ($investment->hold=='2') {
            return response()->json(["message"=>"This Investment is halted at the moment", "status"=>"error"], 400);
        }
        if ($investment->monthtype=='1') {
            return response()->json(["message"=>"Can not pay this Investment", "status"=>"error"], 400);
        }
        $refcode="IP".time();
        $date=date("d-m-Y");
        $paymentrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('FW_KEYT'),
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
        // $payment=PaymentHistory::create([
        //     'transfercode'=>$refcode,
        //     'investmentid'=>$investment->investmentid,
        //     'investorid'=>$investment->investor,
        //     'accountnumber'=>$investment->accountnumber,
        //     'bankcode'=>$investment->bankcode,
        //     'amount'=>$investment->return,
        //     'pdate'=>$date,
        //     'narration'=>"Gavice Investment Payment for ".$date,
        //     'status'=>'0'
        // ]);
        // $this->AddLog(json_encode($payment), 'paymenthistory', 'Created');
        // $newapsf=$investment->amountpaidsofar+$investment->return;
        // $newtr=$investment->timeremaining-1;
        // Investment::where('investmentid', $investment->investmentid)->update([
        //     'amountpaidsofar'=>$newapsf,
        //     'timeremaining'=>$newtr,
        //     'lastpaymentdate'=>$date
        // ]);
        // $this->AddLog(json_encode($investment), 'investment', 'PayUpdate');
        return response()->json([
            "message"=>"Investment Payed Successfully",
            "status" => "success",
            //'payment' => $payment,
        ], 200);
    }
    public function paybulkWeeklyInvestment(Request $request)
    {
        $admin=auth()->user();
        $currtime=time();
        if(empty($request->otp)){
            return response()->json(["message" => "OTP is Required. Try again", "status" => "error"], 400);
        }
        if($request->otp!=$admin->otp){
            return response()->json(["message" => "OTP Verification Failed. Try again", "status" => "error"], 400);
        }elseif($currtime>$admin->expiration){
            return response()->json(["message" => "OTP Expired.", "status" => "error"], 400);
        }

        $investments=Investment::where('type', '1')->where('status', '1')->where('approve', '1')->where('hold', '0')->get();
        $refcode="IP".time();
        $date=date("d-m-Y");
        /////////////////
        ///Get Bulk data
        ///////////////////
        $bulkdata=[];
        $k=1;
        foreach ($investments as $investment) {
            $newdata=(object)[
                "bank_code"=> $investment->bankcode,
                "account_number"=> $investment->accountnumber,
                "amount"=> intval($investment->return),
                "narration"=> "Gavice Investment Payment for ".$date,
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
        $k=1;
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
            $newapsf=$investment->amountpaidsofar+$investment->return;
            $newtr=$investment->timeremaining-1;
            Investment::where('investmentid', $investment->investmentid)->update([
                'amountpaidsofar'=>$newapsf,
                'timeremaining'=>$newtr,
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
    public function paybulkWeeklyFrozenInvestment(Request $request)
    {
        $admin=auth()->user();
        $currtime=time();
        if(empty($request->otp)){
            return response()->json(["message" => "OTP is Required. Try again", "status" => "error"], 400);
        }
        if($request->otp!=$admin->otp){
            return response()->json(["message" => "OTP Verification Failed. Try again", "status" => "error"], 400);
        }elseif($currtime>$admin->expiration){
            return response()->json(["message" => "OTP Expired.", "status" => "error"], 400);
        }
        $investments=Investment::where('type', '1')->where('status', '1')->where('approve', '1')
        ->where('hold', '1')->get();
        // ->whereIn('investmentid', $request->investmentlist)->get();
        $refcode="IP".time();
        $date=date("d-m-Y");
        /////////////////
        ///Get Bulk data
        ///////////////////
        $bulkdata=[];
        $k=1;
        foreach ($investments as $investment) {
            $newdata=(object)[
                "bank_code"=> $investment->bankcode,
                "account_number"=> $investment->accountnumber,
                "amount"=> intval($investment->return),
                "narration"=> "Gavice Investment Payment for ".$date,
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
            "Authorization" => "Bearer ".env('FW_KEYT'),
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
        $k=1;
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
            $newapsf=$investment->amountpaidsofar+$investment->return;
            $newtr=$investment->timeremaining-1;
            Investment::where('investmentid', $investment->investmentid)->update([
                'amountpaidsofar'=>$newapsf,
                'timeremaining'=>$newtr,
                'lastpaymentdate'=>$date
            ]);
            $k++;
        }
        $this->AddLog(json_encode($bulkdata), 'weekbulkpayment', 'SuccessPayment');
        return response()->json([
            "message"=>"Investment Payment Batch 2 Dispatched Successfully",
            "status" => "success",
        ], 200);
    }

    public function paybulkMonthlyInvestment(Request $request)
    {
        $admin=auth()->user();
        $currtime=time();
        if(empty($request->otp)){
            return response()->json(["message" => "OTP is Required. Try again", "status" => "error"], 400);
        }
        if($request->otp!=$admin->otp){
            return response()->json(["message" => "OTP Verification Failed. Try again", "status" => "error"], 400);
        }elseif($currtime>$admin->expiration){
            return response()->json(["message" => "OTP Expired.", "status" => "error"], 400);
        }

        $investments=Investment::where('type', '2')->where('status', '1')->where('approve', '1')
        ->where('hold', '0')
        ->where('monthtype', '0')->get();
        $refcode="IP".time();
        $date=date("d-m-Y");
        $mdate=date("F-Y");
        /////////////////
        ///Get Bulk data
        ///////////////////
        $bulkdata=[];
        $k=1;
        foreach ($investments as $investment) {
            $newdata=(object)[
                "bank_code"=> $investment->bankcode,
                "account_number"=> $investment->accountnumber,
                "amount"=> intval($investment->return),
                "narration"=> "Gavice Investment Payment for ".$mdate,
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
        $k=1;
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

    public function paybulkMonthlyFrozenInvestment(Request $request)
    {
        $admin=auth()->user();
        $currtime=time();
        if(empty($request->otp)){
            return response()->json(["message" => "OTP is Required. Try again", "status" => "error"], 400);
        }
        if($request->otp!=$admin->otp){
            return response()->json(["message" => "OTP Verification Failed. Try again", "status" => "error"], 400);
        }elseif($currtime>$admin->expiration){
            return response()->json(["message" => "OTP Expired.", "status" => "error"], 400);
        }
        $investments=Investment::where('type', '2')->where('status', '1')->where('approve', '1')
        ->where('hold', '1')
        ->where('monthtype', '0')->get();
        $refcode="IP".time();
        $date=date("d-m-Y");
        $mdate=date("F-Y");
        /////////////////
        ///Get Bulk data
        ///////////////////
        $bulkdata=[];
        $k=1;
        foreach ($investments as $investment) {
            $newdata=(object)[
                "bank_code"=> $investment->bankcode,
                "account_number"=> $investment->accountnumber,
                "amount"=> intval($investment->return),
                "narration"=> "Gavice Investment Payment for ".$mdate,
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
            "Authorization" => "Bearer ".env('FW_KEYT'),
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
        $k=1;
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

    public function paybulkMonthlyInvestment2(Request $request)
    {
        $admin=auth()->user();
        $currtime=time();
        if(empty($request->otp)){
            return response()->json(["message" => "OTP is Required. Try again", "status" => "error"], 400);
        }
        if($request->otp!=$admin->otp){
            return response()->json(["message" => "OTP Verification Failed. Try again", "status" => "error"], 400);
        }elseif($currtime>$admin->expiration){
            return response()->json(["message" => "OTP Expired.", "status" => "error"], 400);
        }
        $investments=Investment::where('type', '2')->where('status', '1')->where('approve', '1')
        ->where('hold', '0')
        ->where('monthtype', '1')->get();
        $refcode="IP".time();
        $date=date("d-m-Y");
        $mdate=date("F-Y");
        /////////////////
        ///Get Bulk data
        ///////////////////
        $bulkdata=[];
        $k=1;
        foreach ($investments as $investment) {
            $newdata=(object)[
                "bank_code"=> $investment->bankcode,
                "account_number"=> $investment->accountnumber,
                "amount"=> intval($investment->return),
                "narration"=> "Gavice Investment Payment for ".$mdate,
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
        $k=1;
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

    public function paybulkMonthlyInvestmentsharp(Request $request)
    {
        $admin=auth()->user();
        $currtime=time();
        if(empty($request->otp)){
            return response()->json(["message" => "OTP is Required. Try again", "status" => "error"], 400);
        }
        if($request->otp!=$admin->otp){
            return response()->json(["message" => "OTP Verification Failed. Try again", "status" => "error"], 400);
        }elseif($currtime>$admin->expiration){
            return response()->json(["message" => "OTP Expired.", "status" => "error"], 400);
        }
        $investments=Investment::where('type', '2')->where('status', '1')->where('hold', '0')
        ->whereIn('investmentid', $request->investmentlist)->get();
        $refcode="IP".time();
        $date=date("d-m-Y");
        $mdate=date("F-Y");
        /////////////////
        ///Get Bulk data
        ///////////////////
        $bulkdata=[];
        $k=1;
        foreach ($investments as $investment) {
            $newdata=(object)[
                "bank_code"=> $investment->bankcode,
                "account_number"=> $investment->accountnumber,
                "amount"=> intval($investment->return),
                "narration"=> "Gavice Investment Payment for ".$mdate,
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
        $k=1;
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

    public function retrypayment(Request $request)
    {
        if (empty($request->paymentid)) {
            return response()->json(["message"=>"Payment Id is required", "status"=>"error"], 400);
        }
        $paymentrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('FW_KEY'),
        ])->post('https://api.flutterwave.com/v3/transfers/'.$request->paymentid.'/retries');
        $res=$paymentrequest->json();
        //print_r($res); exit();
        if (!$res['status']) {
            return response()->json(["message" => "An Error occurred while fetching account", "status" => "error"], 400);
        }
        if ($res['status']=='error') {
            return response()->json(["message" => "An Error occurred while fetching account", "status" => "error"], 400);
        }
        return response()->json([
            "message"=>"Investment Payed Successfully",
            "status" => "success",
        ], 200);
    }

    public function freezebiginvestments(Request $request)
    {
        $check=Investment::where('hold', '1')->where('type', $request->type)->where('status', '1')->first();
        if($check!==null){
            return response()->json(["message" => "Investments Already Splitted. Try again", "status" => "error"], 400);
        }
        $investments=Investment::where('type', $request->type)->where('status', '1')
        ->where('return', '>=', $request->stopamount)->where('monthtype', '0')->where('approve', '1')
        ->where('accountnumber', '!=', '6192080675')->where('hold', '0')->orderby('return', 'desc')
        ->get();
        $investments=json_decode(json_encode($investments), true);
        $totalamount=0;
        $investmentlist=[];
        foreach ($investments as $investment) {
            if($totalamount>=$request->splitamount){
                break;
            }else{
                $this->freeze($investment['investmentid']);
                array_push($investmentlist, $investment['investmentid']);
            }
            $totalamount=$totalamount+$investment['return'];
        }
        $investments=Investment::whereIn('investmentid', $investmentlist)
        ->orderby('return', 'desc')
        ->get();
        return response()->json([
            "investments"=>$investments,
            "message"=>"Action done Successfully",
            "status" => "success",
        ], 200);
    }
    public function freezebiginvestments2(Request $request)
    {
        $investments=Investment::where('type', $request->type)->where('status', '1')
        ->where('monthtype', '0')->where('hold', '1')->where('return', '>=', $request->stopamount)
        ->where('accountnumber', '!=', '6192080675')->orderby('return', 'desc')
        ->get();
        $investments=json_decode(json_encode($investments), true);
        $totalamount=0;
        $investmentlist=[];
        foreach ($investments as $investment) {
            if($totalamount>=$request->splitamount){
                break;
            }else{
                $this->freeze2($investment['investmentid']);
                array_push($investmentlist, $investment['investmentid']);
            }
            $totalamount=$totalamount+$investment['return'];
        }
        $investments=Investment::whereIn('investmentid', $investmentlist)
        ->orderby('return', 'desc')
        ->get();
        return response()->json([
            "investments"=>$investments,
            "message"=>"Action done Successfully",
            "status" => "success",
        ], 200);
    }
    public function getfrozeninvestmentsbyid(Request $request)
    {
        $investments=Investment::where('type', $request->type)->where('status', '1')
        ->where('hold', '1')
        ->get();
        $investments=json_decode(json_encode($investments), true);
        $investmentlist=[];
        foreach ($investments as $investment) {
            array_push($investmentlist, $investment['investmentid']);
        }
        return response()->json([
            "investmentlist"=>$investmentlist,
            "message"=>"Successful",
            "status" => "success",
        ], 200);
    }
    public function getfrozeninvestmentsbyid2(Request $request)
    {
        $investments=Investment::where('type', $request->type)->where('status', '1')
        ->where('hold', '2')
        ->get();
        $investments=json_decode(json_encode($investments), true);
        $investmentlist=[];
        foreach ($investments as $investment) {
            array_push($investmentlist, $investment['investmentid']);
        }
        return response()->json([
            "investmentlist"=>$investmentlist,
            "message"=>"Successful",
            "status" => "success",
        ], 200);
    }

    public function getfrozeninvestmentsaccountdetails(Request $request)
    {
        $investments=Investment::where('type', $request->type)->where('status', '1')
        ->where('hold', '1')
        ->get();
        $investments=json_decode(json_encode($investments), true);
        $investorlist=[];
        $totalamount=0;
        foreach ($investments as $investment) {
            $acct=$this->getAccountDetails($investment['accountnumber'], $investment['bankcode']);
            $acct['amount']=number_format($investment['return']);
            $totalamount=$totalamount+$investment['return'];
            array_push($investorlist, $acct);
        }
        return response()->json([
            "investorlist"=>$investorlist,
            "totalamount"=>number_format($totalamount),
            "message"=>"Successful",
            "status" => "success",
        ], 200);
    }

    public function getfrozeninvestmentsaccountdetails2(Request $request)
    {
        $investments=Investment::where('type', $request->type)->where('status', '1')
        ->where('hold', '2')
        ->get();
        $investments=json_decode(json_encode($investments), true);
        $investorlist=[];
        $totalamount=0;
        foreach ($investments as $investment) {
            $acct=$this->getAccountDetails($investment['accountnumber'], $investment['bankcode']);
            $acct['amount']=number_format($investment['return']);
            $totalamount=$totalamount+$investment['return'];
            array_push($investorlist, $acct);
        }
        return response()->json([
            "investorlist"=>$investorlist,
            "totalamount"=>number_format($totalamount),
            "message"=>"Successful",
            "status" => "success",
        ], 200);
    }
    public function getAccountDetails($accountnumber, $bank)
    {
        $acctrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('FW_KEY'),
        ])->post('https://api.flutterwave.com/v3/accounts/resolve', [
            "account_number"=> $accountnumber,
            "account_bank"=> $bank,
        ]);
        $res=$acctrequest->json();
        //print_r($res); exit();
        $details=$res['data'];
        return [
            'accountnumber' => $accountnumber,
            //'bankcode' => $bank,
            'bankname'=> $this->getbankname($bank),
            'accountname'=> $details['account_name'],
        ];
    }
    public function getbankname($bankcode){
        return Bank::where('bankcode', $bankcode)->first()->name;
    }
    public function freeze($investmentid){
        Investment::where('investmentid', $investmentid)->update([
            'hold'=>'1',
        ]);
        return;
    }
    public function freeze2($investmentid){
        Investment::where('investmentid', $investmentid)->update([
            'hold'=>'2',
        ]);
        return;
    }
    public function freezeinvestment(Request $request){
        $investment=Investment::where('investmentid', $request->investmentid)->update([
            'hold'=>'1',
        ]);
        return response()->json([
            "investment"=>$investment,
            "message"=>"Successful",
            "status" => "success",
        ], 200);
    }

    public function unfreezeinvestment(Request $request){
        $investment=Investment::where('investmentid', $request->investmentid)->update([
            'hold'=>'0',
        ]);
        return response()->json([
            "investment"=>$investment,
            "message"=>"Successful",
            "status" => "success",
        ], 200);
    }
    public function unfreezeinvestments(Request $request){
        Investment::whereIn('investmentid', $request->investmentlist)->update([
            'hold'=>'0',
        ]);
        return true;
    }

    public function unexpireinvestments(Request $request){
        Investment::whereIn('investmentid', $request->investmentlist)->update([
            'status'=>'1',
        ]);
        return response()->json([
            "message"=>"Successful",
            "status" => "success",
        ], 200);
    }
    public function haltinvestments(Request $request){
        Investment::where('type', '1')->where('status', '1')->whereIn('investmentid', $request->investmentlist)->update([
            'hold'=>'2',
        ]);
        return response()->json([
            "message"=>"Successful",
            "status" => "success",
        ], 200);
    }
    public function unhaltinvestments(Request $request){
        Investment::where('type', '1')->where('status', '1')->whereIn('investmentid', $request->investmentlist)->update([
            'hold'=>'0',
        ]);
        return response()->json([
            "message"=>"Successful",
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

    public function approve(Investment $investment)
    {
        //print_r($investment); exit();
        $this->AddLog(json_encode($investment), 'investment', 'Approved');
        $investment->update([
            'approve' => '1'
        ]);
        $response=[
            "message" => "Investment Approved Successfully",
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
        $no_of=$no_of-1;
        if ($duration=='1') {
            $date->modify("+ $no_of weeks");
        } else {
            $date->modify("+ $no_of months");
        }
        return $date->format('d-m-Y');
    }

    public function GetPayingAmount(Request $request)
    {
        $investments=Investment::where('type', '1')->where('status', '1')->whereIn('hold', ['0', '1'])->get();
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

    public function GetMPayingAmount(Request $request)
    {
        $investments=Investment::where('type', '2')->where('status', '1')
        ->where('monthtype', '0')->whereIn('hold', ['0', '1'])->get();
        $totalamount=0;
        foreach ($investments as $investment) {
            $totalamount=$totalamount+intval($investment->return);
        }
        return response()->json([
            "message"=>"Month Investment Payout Amount Generated Successfully",
            "status" => "success",
            'amount' => $totalamount,
        ], 200);
    }
    public function GetSplitPayingAmount(Request $request)
    {
        $investments=Investment::where('type', $request->type)->where('status', '1')
        ->where('monthtype', '0')->where('hold', '1')->get();
        $totalamount=0;
        foreach ($investments as $investment) {
            $totalamount=$totalamount+intval($investment->return);
        }
        return response()->json([
            "message"=>"Amount Generated Successfully",
            "status" => "success",
            'amount' => $totalamount,
        ], 200);
    }

    public function payweekRemaining(Request $request)
    {
        $investments=Investment::where('type', '1')->where('status', '1')->whereNull('lastpaymentdate')->get();
        $refcode="IP".time();
        $date=date("d-m-Y");
        /////////////////
        ///Get Bulk data
        ///////////////////
        $bulkdata=[];
        $k=1;
        foreach ($investments as $investment) {
            $newdata=(object)[
                "bank_code"=> $investment->bankcode,
                "account_number"=> $investment->accountnumber,
                "amount"=> intval($investment->return),
                "narration"=> "Gavice Investment Payment for ".$date,
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
        $k=1;
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

    public function GetRemainingMonthlyAmount(Request $request)
    {
        $date=$request->date;
        $investments=Investment::where('type', '2')->where('status', '1')
        ->where(function($query) use($date){
            $query->whereNull('lastpaymentdate')->orWhere('lastpaymentdate', '!=', $date);
        })->get();
        $totalamount=0;
        foreach ($investments as $investment) {
            $totalamount=$totalamount+intval($investment->return);
        }
        return response()->json([
            "message"=>"Month Remaining Payout Amount Generated Successfully",
            "status" => "success",
            'amount' => $totalamount,
        ], 200);
    }

    public function sendOTP(Request $request)
    {
        $admin=auth()->user();
        $otp=$this->generate_otp();
        $expiration = time()+600;
        $user = Admin::where('username', $admin->username)->update(
            ['otp'=>$otp, 'expiration'=>$expiration],
        );
        $details = [
            //'phone'=>'234'.substr($admin->phone, 0),
            'email' => 'samydov@gmail.com',
            'phone'=>'234'.substr('07065975827', 0),
            'otp'=>$otp,
            'subject' => 'Investible Account Verification',
        ];
        try {
            dispatch(new AdminPhoneOtpJob($details))->delay(now()->addSeconds(1));
        } catch (\Throwable $e) {
            report($e);
            Log::error('Error in sending otp: '.$e->getMessage());
        }
        try {
            dispatch(new EmailOtpJob($details))->delay(now()->addSeconds(1));
        } catch (\Throwable $e) {
            report($e);
            Log::error('Error in sending otp: '.$e->getMessage());
        }
        $response=[
            'email' => $admin->email,
            'phone' => $admin->phone,
            "expiration" => $expiration,
            'message' => 'OTP is successfully sent to '.$this->maskPhoneNumber($admin->phone). ' and '.$admin->email,
            "status" => "success"
        ];
        return response()->json($response, 201);
    }

    public function generate_otp(){
        $data=mt_rand(100000,999999);
        return $data;
    }

    function maskPhoneNumber($number){
        $mask_number =  substr($number, 0, 2) . str_repeat("*", strlen($number)-4) . substr($number, -4);
        return $mask_number;
    }

}

