<?php

namespace App\Traits;

use App\Models\ActionLog;
use Illuminate\Http\Request;

trait ActionLogTrait {

    /**
     * @param Request $request
     * @return $this|false|string
     */
    public function AddLog($details, $module, $action) {
        $admin=auth()->user();
        ActionLog::create([
            'adminid'=>$admin->id,
            'details'=>$details,
            'actionmodule'=>$module,
            'action'=>$action
        ]);
    }

}
