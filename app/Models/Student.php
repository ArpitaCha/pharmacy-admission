<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $table        =   'register_student';
    protected $primaryKey   =   's_id';
    public $timestamps      =   false;

    protected $guarded = [];
    public function institute()
    {
        return $this->hasOne('App\Models\Institute', "i_code", "s_inst_code")->withDefault(function () {
            return new Institute();
        });
    }
    public function role()
    {
        return $this->hasOne('App\Models\Role', "role_id", "u_role_id")->withDefault(function () {
            return new Role();
        });
    }
    public function trade()
    {
        return $this->hasOne('App\Models\Trade', "t_code", "s_trade_code")->withDefault(function () {
            return new Trade();
        });
    }
    public function block()
    {
        return $this->hasOne('App\Models\Block', "id", "s_block")->withDefault(function () {
            return new Block();
        });
    }
    public function state()
    {
        return $this->hasOne('App\Models\State', "state_id_pk", "s_state_id")->withDefault(function () {
            return new State();
        });
    }
    public function district()
    {
        return $this->hasOne('App\Models\District', "district_id_pk", "s_home_district")->withDefault(function () {
            return new District();
        });
    }
    public function subdivision()
    {
        return $this->hasOne('App\Models\Subdivision', "id", "s_subdivision")->withDefault(function () {
            return new Subdivision();
        });
    }
}
