<?php

namespace App\Models\wbscte;

use Illuminate\Database\Eloquent\Model;
use DB;
use Session;

class Rank extends Model
{
    protected $table        =   'jexpo_rank';
    protected $primaryKey   =   'r_id';
    public $timestamps      =   false;

    protected $guarded = [];

    public function user()
    {
        return $this->hasOne('App\Models\wbscte\User', "r_index_num", "r_index_num")->withDefault(function () {
            return new User();
        });
    }
}
