<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Session;

class Schedule extends Model
{
    protected $table        =   'schedule_master';
    protected $primaryKey   =   'sch_id';
    public $timestamps      =   false;

    protected $guarded = [];
}
