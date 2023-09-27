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
    public function investmentOwner()
    {
        return $this->hasOne(Investor::class, 'codenumber', 'investorid');
    }
    public function investment()
    {
        return $this->hasOne(Investment::class, 'investmentid', 'investmentid');
    }
    public function bank()
    {
        return $this->hasOne(Bank::class, 'bankcode', 'bankcode');
    }
}
