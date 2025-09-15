<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthPermission extends Model
{
    use HasFactory;

    protected $table        =   'auth_roles_permissions';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;


    protected $guarded = [];
}
