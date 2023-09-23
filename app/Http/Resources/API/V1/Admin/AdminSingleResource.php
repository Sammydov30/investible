<?php

namespace App\Http\Resources\API\V1\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminSingleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            //'username' => $this->username,
            'name' => $this->name,
            'role'   => $this->role,
            'status'   => $this->status,
            'profilepicture'   => $this->profilepicture,
            'lastlogin'   => $this->lastlogin,
        ];
    }
}
