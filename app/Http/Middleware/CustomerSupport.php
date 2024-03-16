<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerSupport
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        //$user = DB::table('admins')->select('role')->where('id', 1)->first();
        //Auth::check();
        $user=Auth::user();

        if ($user && ( (int)$user->role !== 0 && (int)$user->role !== 1)){
            // fail and redirect silently if we already have a user with that role
            return response()->json(["message" => "Not Authorized", "status" => "error"], 401);
        }

        return $next($request);
    }
}
