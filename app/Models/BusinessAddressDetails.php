<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessAddressDetails extends Model
{
    // use HasFactory;
    protected $table        =   'business_address_details';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    protected $guarded = [];
    public function block()
    {
        return $this->hasOne('App\Models\Block', "id", "business_block")->withDefault(function () {
            return new Block();
        });
    }
    public function state()
    {
        return $this->hasOne('App\Models\State', "state_id_pk", "business_state_id")->withDefault(function () {
            return new State();
        });
    }
    public function district()
    {
        return $this->hasOne('App\Models\District', "district_id_pk", "business_district_id")->withDefault(function () {
            return new District();
        });
    }
    public function subdivision()
    {
        return $this->hasOne('App\Models\Subdivision', "id", "business_subdivision")->withDefault(function () {
            return new Subdivision();
        });
    }
}
