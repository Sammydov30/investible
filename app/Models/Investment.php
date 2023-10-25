<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
