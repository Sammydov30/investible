<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkPaymentHistory extends Model
{
    use HasFactory;
    protected $fillable = [
        'transerid'
    ];
}
