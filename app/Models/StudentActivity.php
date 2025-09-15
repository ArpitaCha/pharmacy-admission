<?php

namespace App\Models\wbscte;

use Illuminate\Database\Eloquent\Model;
use DB;
use Session;

class StudentActivity extends Model
{
    protected $table        =   'jexpo_student_activities';
    protected $primaryKey   =   'a_id';
    public $timestamps      =   false;

    protected $guarded = [];
}
