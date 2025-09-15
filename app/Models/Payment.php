<?php

namespace App\Models\wbscte;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table        =   "jexpo_payments";
    protected $primaryKey   =   'pmnt_id';
    public $timestamps      =   false;

    protected $guarded = [];
}
