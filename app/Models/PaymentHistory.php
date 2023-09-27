<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'transfercode',
        'investmentid',
        'investorid',
        'accountnumber',
        'bankcode',
        'accountname',
        'amount',
        'pdate',
        'narration',
        'status'
    ];
}
