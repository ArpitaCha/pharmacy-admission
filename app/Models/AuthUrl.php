<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthUrl extends Model
{
    use HasFactory;

    protected $table        =   'auth_urls';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;


    protected $guarded = [];
}
