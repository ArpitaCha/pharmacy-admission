<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Session;

class Role extends Model
{
    protected $table        =   'role_master';
    protected $primaryKey   =   'role_id';
    public $timestamps      =   false;

    protected $fillable = [
        'role_ref',
        'role_name',
        'role_description',
        'role_order',
        'created_at',
        'updated_at',
        'is_active'
    ];
}
