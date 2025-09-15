<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Session;

class District extends Model
{
    protected $table        =   'district_master';
    protected $primaryKey   =   'district_id_pk';
    public $timestamps      =   false;

    protected $guarded = [];
    public function state()
    {
        return $this->hasOne('App\Models\State', "state_id_pk", "state_id_fk")->withDefault(function () {
            return new State();
        });
    }
    public function institutes()
    {
        return $this->hasMany(Institute::class, 'i_dist_code', 'district_id_pk');
    }
}
