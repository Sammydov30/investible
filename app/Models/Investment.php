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
        'status'
    ];
}
