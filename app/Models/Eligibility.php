<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eligibility extends Model
{
    // use HasFactory;
    protected $table        =   'config_eligibility';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    protected $guarded = [];
}
