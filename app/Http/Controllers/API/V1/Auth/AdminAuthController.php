<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\RegisterRequest;
use App\Http\Requests\CheckOtpRequest;
use App\Jobs\Admin\AdminPhoneOtpJob;
use App\Jobs\Admin\EmailOtpJob;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminAuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $admin = Admin::where('username', $request->username)->first();
        if (!$admin || (md5(sha1($request->password))!=$admin->password)) {
            return response()->json(["message" => "The provided credentials are incorrect", "status" => "error"], 400);
        }
        if ($admin->status=='0') {
            return response()->json(["message" => "This Admin account is Inactive.", "status" => "error"], 400);
        }
        $otp=$this->generate_otp();
        $expiration = time()+600;
        $user = Admin::where('username', $request->username)->update(
            ['otp'=>$otp, 'expiration'=>$expiration],
        );
        $details = [
            'email' => $admin->email,
            'phone'=>'234'.substr($admin->phone, 0),
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
            'message' => 'OTP is successfully sent to '.$this->maskPhoneNumber($admin->phone),
            "status" => "success"
        ];
        return response()->json($response, 201);

    }

    public function check_otp(CheckOtpRequest $request)
    {
        $admin=Admin::where('phone', $request->phone)->first();
        $currtime=time();
        if($admin->otp==$request->otp){
            $user = Admin::where('phone', $request->phone)->update(
                ['lastlogin'=>time()],
            );
            $response=[
                'token' => $admin->createToken('investible', ['role:admin'])->plainTextToken,
                "status" => "success",
                'admin' => $admin,
            ];
            return response()->json($response, 201);
        }elseif($currtime>$admin->expiration){
            return response()->json(["message" => "OTP Expired.", "status" => "error"], 400);
        }else{
            return response()->json(["message" => "Otp Verification Failed. Try again", "status" => "error"], 400);
        }

    }


    public function generate_otp(){
        $data=mt_rand(100000,999999);
        return $data;
    }

    function maskPhoneNumber($number){
        $mask_number =  substr($number, 0, 2) . str_repeat("*", strlen($number)-4) . substr($number, -4);
        return $mask_number;
    }


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(["message" => "Logout successful", "status" => "success"], 200);
        // auth()->user()->tokens()->delete();
        // return response()->json(['message'=>'Logout Successful']);
    }
}
