<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use function PHPUnit\Framework\isNull;

class Investment extends Model
{
    use HasFactory;
    protected $fillable = [
        'investmentid',
        'investor',
        'nextofkin',
        'account',
        'accountnumber',
        'bankcode',
        'planid',
        'type',
        'amountpaid',
        'amount_to_be_returned',
        'percentage',
        'return',
        'amountpaidsofar',
        'agreementdate',
        'timeduration',
        'timeremaining',
        'startdate',
        'stopdate',
        'period',
        'description',
        'witnessname',
        'witnessaddress',
        'witnessphone',
        'lastpaymentdate',
        'pop',
        'agreementdoc',
        'approve',
        'hold',
        'monthtype',
        'status'
    ];

    public function investmentOwner()
    {
        return $this->hasOne(Investor::class, 'codenumber', 'investor');
    }
    public function nok()
    {
        return $this->hasOne(NextOfKin::class, 'id', 'nextofkin');
    }
    public function bank()
    {
        return $this->hasOne(Bank::class, 'bankcode', 'bankcode');
    }

    // public function toArray()
    // {
    //     $array = parent::toArray();
    //     $url = env('APP_UURL');
    //     $array['pop'] =(isNull($this->pop))? "https://res.cloudinary.com/examqueat/image/upload/v1664654734/handshake.jpg" :
    //     $url.$this->pop;
    //     return $array;
    // }
}
