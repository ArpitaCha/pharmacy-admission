<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Session;

class Board extends Model
{
    protected $table        =   'recognized_board';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    protected $guarded = [];
}
