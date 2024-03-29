<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Investment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BankController extends Controller
{
    public function fetchbanks()
    {
        $banks=Bank::orderBY('name', 'ASC')->get();
        return response()->json($banks, 200);
    }
    public function explodebanks()
    {
        $paymentrequest = Http::withHeaders([
            "content-type" => "application/json",
            "Authorization" => "Bearer ".env('FW_KEY'),
        ])->get('https://api.flutterwave.com/v3/banks/NG');
        $payy=$paymentrequest->json();

        $allbanks=$payy['data'];
        foreach ($allbanks as $bank) {
            Bank::create([
                'bankid' => $bank['id'],
                'bankcode' => $bank['code'],
                'name' => $bank['name'],
            ]);
        }
        die("All done");
    }
    public function fixinvestments()
    {
        $investments=Investment::all();
        foreach ($investments as $investment) {
            $t=trim(explode(' ',$investment->timeduration)[0]);
            $r=trim(str_replace(',', '', $investment->return));
            Investment::where('id', $investment->id)->update([
                'timeduration' => $t,
                'return' => $r,
            ]);
        }
        die("All done");
    }
    public function fixinvestments2()
    {
        $investments=Investment::all();
        foreach ($investments as $investment) {
            $t=trim(explode(' ',$investment->timeduration)[0]);
            $r=trim(str_replace(',', '', $investment->return));
            Investment::where('id', $investment->id)->update([
                'timeduration' => $t,
                'return' => $r,
            ]);
        }
        die("All done");
    }
    public function fixinvestments3()
    {
        Investment::where('status', '1')
        ->where(function($query){
            $query->whereNull('timeremaining')->orWhereNull('amountpaidsofar');
        })->update([
            'timeremaining'=>'49',
            'amountpaidsofar'=>'0'
        ]);
        die("All done");
    }
    public function fixinvestments4(Request $request)
    {
        $date1=$request->date1;
        $date2=$request->date2;
        $date3=$request->date3;
        Investment::where('startdate', $date1)->orWhere('startdate', $date2)->update([
            'stopdate'=>$date3,
        ]);
        die("All done");
    }

    public function fixinvestments5()
    {
        Investment::where('type', '2')->update([
            'status'=>'1'
        ]);
        die("All done");
    }

}
