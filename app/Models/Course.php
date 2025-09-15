<?php

namespace App\Models\wbscte;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $table        =   'wbscte_other_diploma_course_master';
    protected $primaryKey   =   'course_id_pk';
    public $timestamps      =   false;

    protected $fillable = [
        'course_name', 'course_code', 'inst_id', 'course_duration', 'is_active', 'course_affiliation_year', 'course_type'
    ];

    public function institute()
    {
        return $this->hasOne('App\Models\wbscte\Institute', "inst_sl_pk", "inst_id")->withDefault(function () {
            return new Institute();
        });
    }
}
