<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplElgbExam extends Model
{
    // use HasFactory;
    protected $table        =   'appl_elgb_exam';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    protected $guarded = [];
    public function board12th()
    {
        return $this->hasOne('App\Models\Board', "id", "exam_12th_board")->withDefault(function () {
            return new Board();
        });
    }
    public function board10th()
    {
        return $this->hasOne('App\Models\Board', "id", "exam_10th_board")->withDefault(function () {
            return new Board();
        });
    }

    public function state10th()
    {
        return $this->hasOne('App\Models\State', "state_id_pk", "exam_10th_state_code")->withDefault(function () {
            return new State();
        });
    }
    public function state12th()
    {
        return $this->hasOne('App\Models\State', "state_id_pk", "exam_12th_state_code")->withDefault(function () {
            return new State();
        });
    }
    public function district10th()
    {
        return $this->hasOne('App\Models\District', "district_id_pk", "exam_10th_district")->withDefault(function () {
            return new District();
        });
    }
    public function district12th()
    {
        return $this->hasOne('App\Models\District', "district_id_pk", "exam_12th_district")->withDefault(function () {
            return new District();
        });
    }
}
