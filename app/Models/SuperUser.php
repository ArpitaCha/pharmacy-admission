<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Session;

class SuperUser extends Model
{
    protected $table        =   'users_master';
    protected $primaryKey   =   'u_id';
    public $timestamps      =   false;

    protected $guarded = [];

    public function role()
    {
        return $this->hasOne('App\Models\Role', "role_id", "u_role_id")->withDefault(function () {
            return new Role();
        });
    }
    public function district()
    {
        return $this->hasOne('App\Models\District', "district_id_pk", "u_inst_district")->withDefault(function () {
            return new District();
        });
    }
    public function HeadVerifierStudentAssign()
    {
        return $this->hasOne('App\Models\HeadVerifierStudentAssign', "head_verifier_id", "u_id")->withDefault(function () {
            return new HeadVerifierStudentAssign();
        });
    }
    public function institute()
    {
        return $this->hasOne('App\Models\Institute', "i_code", "u_inst_code")->withDefault(function () {
            return new Institute();
        });
    }
}
