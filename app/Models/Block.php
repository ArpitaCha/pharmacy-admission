<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Session;

class Block extends Model
{
    protected $table        =   'block_municipalities';
    protected $primaryKey   =   'block_id_pk';
    public $timestamps      =   false;

    protected $guarded = [];
    public function subdivision()
    {
        return $this->hasOne('App\Models\Subdivision', "id", "subdivision_id")->withDefault(function () {
            return new Subdivision();
        });
    }
}
