<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $table        =   "payment_transaction_tbl";
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    protected $guarded = [];
}
