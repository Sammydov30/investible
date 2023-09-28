<?php

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminCreateRequest;
use App\Http\Requests\Admin\AdminRequest;
use App\Http\Requests\AdminProfileRequest;
use App\Http\Resources\API\V1\Admin\AdminResource;
use App\Http\Resources\API\V1\Admin\AdminSingleResource;
use App\Models\Admin;
use App\Traits\ActionLogTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    use ActionLogTrait;

    public function index(Request $request)
    {
        $user=auth()->user();
        $result = Admin::where('id', '!=', $user->id)->where('role', '!=', '0');
        if (!empty($request->search)) {
            $result->where('name', "like", "%{$request->search}%");
        }
        if (!empty($request->role)) {
            $search=$request->role;
            $result->where('role', $search);
        }
        if (!empty($request->status)) {
            $search=$request->status;
            $result->where('status', $search);
        }
        if (!empty($request->sortBy) && in_array($request->sortBy, ['id', 'created_at'])) {
            $sortBy=$request->sortBy;
        }else{
            $sortBy='id';
        }
        if (!empty($request->sortorder) && in_array($request->sortorder, ['asc', 'desc'])) {
            $sortOrder=$request->sortorder;
        }else{
            $sortOrder='desc';
        }
        if (!empty($request->perpage)) {
            $perPage=$request->perpage;
        } else {
            $perPage=10;
        }
        $admins=$result->orderBY($sortBy, $sortOrder)->paginate($perPage);
        return response()->json($admins, 200);
    }

    public function register(AdminCreateRequest $request)
    {
        $error=array();
        $check2=Admin::where('email', $request->email)->first();
        if ($check2) {
            array_push($error,"Email already exist");
        }

        if(empty($error)){
            $admin = Admin::create([
                'username' => $request->username,
                'firstname' => $request->firstname,
                'lastname'=> $request->lastname,
                'email' => $request->email,
                'phone'=> $request->phone,
                'role' => '1',
                'status' => '1',
                'password' => md5(sha1($request->password)),
            ]);
            $this->AddLog(json_encode($admin), 'admin', 'Created');
            $response=[
                "message" => "Admin Created Successfully",
                'admin' => $admin,
                "status" => "success"
            ];

            return response()->json($response, 201);
        }else{
            return response()->json(["message"=>$error, "status"=>"error"], 400);
        }
    }

    public function show($admin)
    {
        $admin=Admin::find($admin);
        if (!$admin) {
            return response()->json(["message"=>"This record doesn't exist", "status"=>"error"], 400);
        }
        //return new AdminSingleResource($admin);
        $response=[
            "message" => "Admin found",
            'admin' => $admin,
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function update(AdminRequest $request, Admin $admin)
    {
        $error=array();
        // $check=Admin::where('username', $request->username)->where('id', '!=', $admin->id)->first();
        // if ($check) {
        //     array_push($error,"Username already exist");
        // }
        $check2=Admin::where('email', $request->email)->where('id', '!=', $admin->id)->first();
        if ($check2) {
            array_push($error,"Email already exist");
        }
        if(empty($error)){
            $admin->update([
                'firstname' => $request->firstname,
                'lastname'=> $request->lastname,
                'email' => $request->email,
                'phone'=> $request->phone,
                'username' => $request->username,
                'password' => md5(sha1($request->password)),
                'role' => '1',
                'status' => '1',
            ]);
            $this->AddLog(json_encode($admin), 'admin', 'Updated');
            $response=[
                "message" => "Admin Updated Successfully",
                'admin' => $admin,
                "status" => "success"
            ];
            return response()->json($response, 201);
        }else{
            return response()->json(["message"=>$error, "status"=>"error"], 400);
        }
    }

    public function changepassword(Request $request)
    {
        $user=auth()->user();
        $error=array();
        if (empty($request->oldpassword)) {
          array_push($error,"Old Password is Required");
        }
        if (empty($request->newpassword)) {
            array_push($error,"New Password is Required");
        }
        if (empty($request->cnewpassword)) {
            array_push($error,"New Password Confirmation is Required");
        }
        if ($request->cnewpassword!=$request->newpassword) {
            array_push($error,"New Password Mismatch");
        }

        $admin=Admin::where('id', $user->id);
        if (!$admin) {
            array_push($error,"User doesn't exist");
        }
        $newpassword= md5(sha1($request->newpassword));
        $oldpassword= md5(sha1($request->oldpassword));
        $dpassword=$admin->first()->password;
        if ($oldpassword!=$dpassword) {
            array_push($error,"Old Password is Incorrect");
        }
        if(empty($error)){
            $admin->update([
                'password' => $newpassword,
            ]);
            $this->AddLog(json_encode($admin), 'admin', 'ChangePassword');
            $response=[
                "status" => "success",
                "message" => "Password Changed Successfully",
                //'admin' => $admin,
            ];
            return response()->json($response, 201);
        }else{
            return response()->json(["message"=>$error, "status"=>"error"], 400);
        }
    }

    public function destroy(Admin $admin)
    {
        $this->AddLog(json_encode($admin), 'admin', 'Deleted');
        $admin->delete();
        $response=[
            "message" => "Admin Deleted Successfully",
            "status" => "success"
        ];
        return response()->json($response, 200);
    }

    public function getA(Request $request)
    {
        $admin=auth()->user();
        return response()->json([
            'user' => $admin,
            "status" => "success"
        ], 200);
    }

    public function changeprofile(AdminProfileRequest $request)
    {
        $u=auth()->user();
        $user=Admin::where('id', $u->id)->update([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'phone' => $request->phone,
            'email' => $request->email,
        ]);
        $this->AddLog(json_encode($user), 'admin', 'ChangeProfile');
        return response()->json([
            "status" => "success",
            'user' => $user,
            'message' => "Information Changed Successfully",
        ], 200);
    }
}

