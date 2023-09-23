<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\RegisterRequest;
use App\Http\Requests\CheckOtpRequest;
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

        $user = Admin::where('username', $request->username)->update(
            ['lastlogin'=>time()],
        );
        $user = Admin::where('username', $request->username)->first();
        $response=[
            'token' => $admin->createToken('investible', ['role:admin'])->plainTextToken,
            "status" => "success",
            'admin' => $user,
        ];
        return response()->json($response, 201);

    }


    public function generate_otp(){
        $data=mt_rand(100000,999999);
        return $data;
    }


    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(["message" => "Logout successful", "status" => "success"], 200);
        // auth()->user()->tokens()->delete();
        // return response()->json(['message'=>'Logout Successful']);
    }
}
