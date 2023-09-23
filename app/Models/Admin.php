<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use function PHPUnit\Framework\isNull;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'firstname',
        'lastname',
        'phone',
        'username',
        'role',
        'email',
        'password',
        'status',
        'lastlogin',
        'otp',
        'expiration',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function toArray()
    {
        $array = parent::toArray();
        $array['lastlogin'] = (is_null($this->lastlogin)||empty($this->lastlogin)) ? strtotime($this->created_at) :  $this->lastlogin;
        $array['lastloginfull'] = @gmdate('d/m/y h:ia', $array['lastlogin']);
        $array['statusname']= ($this->status=='1') ? "Active" : "Inactive";

        return $array;
    }
}
