<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Session;

class Subdivision extends Model
{
    protected $table        =   'subdivision_tbl';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    protected $guarded = [];
    public function district()
    {
        return $this->hasOne('App\Models\District', "district_id_pk", "district_id",)->withDefault(function () {
            return new District();
        });
    }
}
