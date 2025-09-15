<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fees extends Model
{
    protected $table        =   'od_config_fees';
    protected $primaryKey   =   'cf_id';
    public $timestamps      =   false;

    protected $guarded = [];
}
